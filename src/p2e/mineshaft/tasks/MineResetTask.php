<?php
declare(strict_types=1);

namespace p2e\mineshaft\tasks;


use p2e\mineshaft\events\ClearPlayersFromMineEvent;
use p2e\mineshaft\mines\Mine;
use p2e\mineshaft\MineShaft;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineResetTask extends Task{

    /** @var Mine */
    private $mine;

    public function __construct(Mine $mine, bool $protectWholeWorld){
        $this->mine = $mine;
        $level = $this->mine->getLevel();

        // TODO: add mine specific safe spawn locations.
        $event = new ClearPlayersFromMineEvent($mine, Server::getInstance()->getDefaultLevel()->getSpawnLocation());
        $event->call();
        $destination = $event->getDestination();
        $protectWholeWorld ? $this->prepareEntireWorld($level, $destination) : $this->prepareMineOnly($level, $destination);
    }

    /**
     * Removes all player from the world the mine is in and teleports them to a safe location.
     *
     * @param Level    $level
     * @param Position $destination
     */
    private function prepareEntireWorld(Level $level, Position $destination) : void{
        foreach($level->getPlayers() as $player){
            $player->teleport($destination);
            $this->sendSavedMessage($player);
        }
    }

    /**
     * Removes all players from the area where blocks will be replaced and up to 10 blocks away.
     *
     * @param Level    $level
     * @param Position $destination
     */
    private function prepareMineOnly(Level $level, Position $destination) : void{
        $bb = $this->mine->getBB()->expandedCopy(10, 10, 10);
        foreach($level->getPlayers() as $player){
            if($bb->isVectorInside($player)){
                $player->teleport($destination);
                $this->sendSavedMessage($player);
            }
        }
    }

    private function sendSavedMessage(Player $player) : void{
        // TODO: Replace static message with tranlsatable message id.
        $player->sendMessage(TextFormat::GOLD . "You were teleported to safety while a mine was being reset :)");
    }

    public function onRun(int $currentTick){
        $level = $this->mine->getLevel();
        $blocksSet = 0;
        foreach($this->mine->getOreTable()->getOreMap() as $data){
            if(!($pos = $data[0]) instanceof Vector3 or !($block = $data[1]) instanceof Block){
                continue;
            }
            $level->setBlock($pos, $block);
            $blocksSet++;
        }
        if($this->mine->getTotalBlocks() > $blocksSet){
            MineShaft::getInstance()->getLogger()->debug("Found inconsistency with OreTable for " . $this->mine->getName() . ". Set blocks = $blocksSet but expected " . $this->mine->getTotalBlocks());
        }
        $this->mine->resetRemainingBlocks();
        $this->mine->setLocked(false);
    }

}