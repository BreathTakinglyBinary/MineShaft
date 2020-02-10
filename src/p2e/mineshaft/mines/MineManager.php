<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use p2e\mineshaft\MineShaft;
use p2e\mineshaft\MineShaftConfiguration;
use pocketmine\math\Vector3;
use pocketmine\Server;

class MineManager{

    /** @var Mine[] */
    private $mines;

    /** @var MineProtectionListener[] */
    private $protectionListeners = [];

    /** @var ResetQueue */
    private $resetQueue;

    /** @var MineStatusListener[] */
    private $statusListeners = [];


    public function __construct(){
        $this->resetQueue = new ResetQueue();
        $this->loadFromConfig();
    }

    public function registerMine(Mine $mine, bool $force = false){
        $mineName = $mine->getName();
        if(isset($this->mines[$mineName])){
            if(!$force){
                MineShaft::getInstance()->getLogger()->error("Tried to register duplicate mine  $mineName");
                return;
            }
            MineShaft::getInstance()->getLogger()->warning("Overwriting existing mine $mineName");
        }
        $this->mines[$mineName] = $mine;
        $this->protectionListeners[$mineName] = new MineProtectionListener($mine);
        $this->statusListeners[$mineName] = new MineStatusListener($mine);
        Server::getInstance()->getPluginManager()->registerEvents($this->protectionListeners[$mineName], MineShaft::getInstance());
        Server::getInstance()->getPluginManager()->registerEvents($this->statusListeners[$mineName], MineShaft::getInstance());
    }

    /**
     * @return ResetQueue
     */
    public function getResetQueue() : ResetQueue{
        return $this->resetQueue;
    }

    public function tick() : void{
        if(MineShaft::getProperties()->getRefillType() === MineShaftConfiguration::REFILL_TYPE_TIME and MineShaft::getProperties()->isAutoRefillEnabled()){
            $this->checkLastReset();
        }
        $this->resetQueue->processNext();
    }

    private function checkLastReset(): void{
        $currentTime = new \DateTime();
        foreach($this->mines as $mine){
            if(($mine->getLastReset()->add(MineShaft::getProperties()->getRefillInterval())) <= $currentTime){
                $this->resetQueue->addMine($mine);
            }
        }
    }

    /**
     * @param string $mineName
     *
     * @return Mine|null
     */
    public function getMine(string $mineName) : ?Mine{
        return $this->mines[$mineName] ?? null;
    }

    /**
     * @return Mine[]
     */
    public function getMines() : array{
        return $this->mines;
    }

    public function loadFromConfig() : void{
        MineShaft::getInstance()->saveResource("mines.yml", false);

        $config = yaml_parse_file(MineShaft::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "mines.yml");
        foreach($config as $mineName => $properties){
            $error = false;

            if(!isset($properties["level"])){
                $error = true;
                $this->missingEntryError("level", $mineName);
            }elseif(!is_string($properties["level"])){
                $error = true;
                $this->invalidEntryError("level", $mineName);
            }else{
                $levelName = $properties["level"];
                if(!Server::getInstance()->isLevelGenerated($levelName)){
                    $error = true;
                    $this->invalidEntryError("level", $mineName);
                    continue;
                }
                if(!Server::getInstance()->isLevelLoaded($levelName) and !Server::getInstance()->loadLevel($levelName)){
                    $this->invalidEntryError("level", $mineName);
                    continue;
                }
                $level = Server::getInstance()->getLevelByName($levelName);
            }

            if(!isset($properties["pos1"])){
                $error = true;
                $this->missingEntryError("pos1", $mineName);
            }elseif(!($pos1 = $this->verifyPosition($properties["pos1"])) instanceof Vector3){
                $error = true;
                $this->invalidEntryError("pos1", $mineName);
            }

            if(!isset($properties["pos2"])){
                $error = true;
                $this->missingEntryError("pos2", $mineName);
            }elseif(!($pos2 = $this->verifyPosition($properties["pos2"])) instanceof Vector3){
                $error = true;
                $this->invalidEntryError("pos2", $mineName);
            }

            $ores = [];
            if(!isset($properties["ores"])){
                $error = true;
                $this->missingEntryError("ores", $mineName);
            }elseif(!is_array($properties["ores"])){
                $error = true;
                $this->invalidEntryError("ores", $mineName);
            }else{
                $ores = $this->verifyOres($properties["ores"], $mineName);
                if(empty($ores)){
                    $error = true;
                    $this->invalidEntryError("ores", $mineName);
                }
            }

            if($error){
                MineShaft::getInstance()->getLogger()->error("Failed to add $mineName.  Check log for error information.");
            }else{
                $this->registerMine(new Mine($mineName, $level, $pos1, $pos2, new OreTable($ores)));
            }
        }

    }

    /**
     * Returns a Vector3 if the array contains valid data.  False if there is an error.
     *
     * @return Vector3|false
     */
    private function verifyPosition(array $coords){
        if(!isset($coords["x_coord"]) or !is_int($coords["x_coord"]) or !isset($coords["y_coord"]) or !is_int($coords["y_coord"]) or !isset($coords["z_coord"]) or !is_int($coords["z_coord"])){
            return false;
        }

        return new Vector3($coords["x_coord"], $coords["y_coord"], $coords["z_coord"]);
    }

    /**
     * Intended to verify strings that match the structrure <id>;<meta>;<weight>
     * All entries must be integers.
     *
     * @param array  $rawOreStrings
     * @param string $mineName
     *
     * @return array
     */
    private function verifyOres(array $rawOreStrings, string $mineName) : array{
        $ores = [];
        foreach($rawOreStrings as $oreString){
            if(!is_string($oreString)){
                $this->invalidEntryError("ores", $mineName);
                continue;
            }
            $oreSplit = explode(";", $oreString);
            if(count($oreSplit) !== 3){
                $this->invalidEntryError("$oreString in ores", $mineName);
                continue;
            }
            $valid = false;
            foreach($oreSplit as $key => $value){
                $valid = false;

                if(!is_numeric($value)){
                    $this->invalidEntryError("$oreString in ores", $mineName, "Value must be <int>;<int>;<int>");
                    break;
                }
                $valid = true;
            }
            if(!$valid){
                continue;
            }
            $ores[] = ["id" => (int) $oreSplit[0], "meta" => (int) $oreSplit[1], "weight" => (int) $oreSplit[2]];
        }

        return $ores;
    }

    private function invalidEntryError(string $entry, string $mineName, string $additionalMessage = "") : void{
        MineShaft::getInstance()->getLogger()->warning("Invalid $entry entry found for \"$mineName\" in mines.yml. $additionalMessage");
    }

    private function missingEntryError(string $entry, string $mineName) : void{
        MineShaft::getInstance()->getLogger()->warning("No $entry entry found for \"$mineName\" in mines.yml.");
    }

}