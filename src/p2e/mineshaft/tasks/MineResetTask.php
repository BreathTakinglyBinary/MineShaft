<?php
declare(strict_types=1);

namespace p2e\mineshaft\tasks;


use p2e\mineshaft\events\ClearPlayersFromMineEvent;
use p2e\mineshaft\mines\Mine;
use p2e\mineshaft\mines\OreTable;
use p2e\mineshaft\MineShaft;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MineResetTask extends AsyncTask{

    /** @var AxisAlignedBB */
    private $bb;

    private $blocksSet = 0;

    /** @var array */
    private $chunks = [];

    /** @var int */
    private $levelId;

    /** @var string */
    private $mineName;

    /** @var OreTable */
    private $oreTable;

    public function __construct(Mine $mine, bool $protectWholeWorld){
        $this->mineName = $mine->getName();
        $level = $mine->getLevel();
        $this->bb = $mine->getBB();
        $this->oreTable = $mine->getOreTable();
        $this->levelId = $level->getId();
        for($x = $this->bb->minX; $x-16 <= $this->bb->maxX; $x += 16){
            for($z = $this->bb->minZ; $z-16 <= $this->bb->maxZ; $z += 16){
                $chunk = $level->getChunk($x >> 4, $z >> 4, true);
                $this->chunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->fastSerialize();
            }
        }

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
        $bb = $this->bb->expandedCopy(10, 10, 10);
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

    public function onRun(){
        $oreTable = $this->oreTable;
        $chunks = [];
        foreach($this->chunks as $chunkHash => $chunkBlob){
            $chunks[$chunkHash] = Chunk::fastDeserialize($chunkBlob);
        }
        $bb = $this->bb;
        $blocksSet = 0;
        for($x = (int) $bb->minX; $x <= $bb->maxX; $x++){
            $chunkX = $x >> 4;
            for($z = (int) $bb->minZ; $z <= $bb->maxZ; $z++){
                $chunkZ = $z >> 4;
                $currentChunk = $chunks[Level::chunkHash($chunkX, $chunkZ)];
                assert($currentChunk instanceof Chunk);
                for($y = (int) $bb->minY; $y <= $bb->maxY; $y++){
                    $block = $oreTable->getRandomEntry();
                    $subChunk = $currentChunk->getSubChunk($y >> 4, true);
                    $subChunk->setBlock(($x & 0x0f) , ($y & 0x0f), ($z & 0x0f), $block->getId() & 0xff, $block->getDamage() & 0xff);
                    $blocksSet++;
                }
            }
        }
        $this->blocksSet = $blocksSet;
        $this->setResult($chunks);
    }

    public function onCompletion(Server $server){
            $level = $server->getLevel($this->levelId);
            if ($level instanceof Level) {
                foreach ($this->getResult() as $hash => $chunk) {
                    Level::getXZ($hash, $x, $z);
                    $level->setChunk($x, $z, $chunk, true);

                }
            }
            $mine = MineShaft::getInstance()->getMineManager()->getMine($this->mineName);
        $mine->resetRemainingBlocks();
        $mine->setLocked(false);
    }

}