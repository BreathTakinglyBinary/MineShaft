<?php
declare(strict_types=1);

namespace p2e\mineshaft\tasks;


use p2e\mineshaft\events\ClearPlayersFromMineEvent;
use p2e\mineshaft\mines\Mine;
use p2e\mineshaft\mines\OreTable;
use p2e\mineshaft\MineShaft;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
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

    /**
     * @param Mine $mine
     * @param bool $protectWholeWorld
     * @return void
     */
    public function __construct(Mine $mine, bool $protectWholeWorld){
        $this->mineName = $mine->getName();
        $level = $mine->getWorld();
        $this->bb = $mine->getBB();
        $this->oreTable = $mine->getOreTable();
        $this->levelId = $level->getId();
        for($x = $this->bb->minX; $x-16 <= $this->bb->maxX; $x += 16){
            for($z = $this->bb->minZ; $z-16 <= $this->bb->maxZ; $z += 16){
                $level->loadChunk($x >> 4, $z >> 4);
                $chunk = $level->getChunk($x >> 4, $z >> 4);
                
                $this->chunks[World::chunkHash($x >> 4, $z >> 4)] = $chunk;
                
            }
        }


        $event = new ClearPlayersFromMineEvent($mine);
        $event->call();
        $destination = $event->getDestination();
        $protectWholeWorld ? $this->prepareEntireWorld($level, $destination) : $this->prepareMineOnly($level, $destination);
    }

    /**
     * Removes all player from the world the mine is in and teleports them to a safe location.
     *
     * @param World    $level
     * @param Position $destination
     */
    private function prepareEntireWorld(World $level, Position $destination) : void{
        foreach($level->getPlayers() as $player){
            $player->teleport($destination);
            $this->sendSavedMessage($player);
        }
    }

    /**
     * Removes all players from the area where blocks will be replaced and up to 10 blocks away.
     *
     * @param World    $level
     * @param Position $destination
     */
    private function prepareMineOnly(World $level, Position $destination) : void{
        $bb = $this->bb->expandedCopy(10, 10, 10);
        foreach($level->getPlayers() as $player){
            if($bb->isVectorInside($player->getPosition()->asVector3())){
                $player->teleport($destination);
                $this->sendSavedMessage($player);
            }
        }
    }

    /**
     * @param Player $player
     */
    private function sendSavedMessage(Player $player) : void{
        $player->sendMessage(TextFormat::GOLD . "You were teleported to safety while a mine was being reset :)");
    }

    public function onRun() : void{
        $oreTable = $this->oreTable;
        $chunks = [];
        foreach($this->chunks as $chunkHash => $chunkBlob){
            $chunks[$chunkHash] = $chunkBlob;
        }
        $bb = $this->bb;
        $blocksSet = 0;
        for($x = (int) $bb->minX; $x <= $bb->maxX; $x++){
            $chunkX = $x >> 4;
            for($z = (int) $bb->minZ; $z <= $bb->maxZ; $z++){
                $chunkZ = $z >> 4;
                $currentChunk = $chunks[World::chunkHash($chunkX, $chunkZ)];
                
                assert($currentChunk instanceof Chunk);
                for($y = (int) $bb->minY; $y <= $bb->maxY; $y++){
                    $block = $oreTable->getRandomEntry();
                    $subChunk = $currentChunk->getSubChunk($y >> 4);
                    $currentChunk->setFullBlock(($x & 0x0f) , ($y & 0x0f), ($z & 0x0f), $block->getId() & 0xff);
//                     $subChunk->setBlock(($x & 0x0f) , ($y & 0x0f), ($z & 0x0f), $block->getId() & 0xff, $block->getDamage() & 0xff);
                    $blocksSet++;
                }
            }
        }
        $this->blocksSet = $blocksSet;
        $this->setResult($chunks);
    }

    public function onCompletion(): void{
            $server = Server::getInstance();
            $level = $server->getWorldManager()->getWorld($this->levelId);
            if ($level instanceof World) {
                foreach ($this->getResult() as $hash => $chunk) {
                    World::getXZ($hash, $x, $z);
                    $level->setChunk($x, $z, $chunk);

                }
            }
            $mine = MineShaft::getInstance()->getMineManager()->getMine($this->mineName);
        $mine->resetRemainingBlocks();
        $mine->setLocked(false);
    }

}