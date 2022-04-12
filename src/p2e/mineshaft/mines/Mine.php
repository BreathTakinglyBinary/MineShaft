<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Mine{

    /** @var AxisAlignedBB */
    private $bb;

    /** @var \DateTime */
    private $lastReset;

    /** @var World */
    private $level;

    /** @var bool */
    private $locked = false;

    /** @var string */
    private $name;

    /** @var OreTable */
    private $oreTable;

    /** @var Vector3 */
    private $pos1;

    /** @var Vector3 */
    private $pos2;

    /** @var int */
    private $remainingBlocks;

    /** @var array */
    private $removedBlocks = [];

    /** @var Vector3 */
    private $spawnLocation;

    /** @var int */
    private $totalBlocks;

    public function __construct(string $name, World $level, Vector3 $pos1, Vector3 $pos2, array $ores, Vector3 $spawn){
        $this->name = $name;
        $this->level = $level;
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
        $this->spawnLocation = $spawn;
        $this->updateBB();

        $this->oreTable = new OreTable($ores);
        $this->calculateTotalBlocks();
        $this->resetRemainingBlocks();
    }
    
    /**
     * Returns the mine name
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

    /**
     * Get's the world for the mine
     * @return World
     */
    public function getWorld() : World{
        return $this->level;
    }

    /**
     * Locks the mine
     * @param bool $enabled
     * @return void
     */
    public function setLocked(bool $enabled = true) : void{
        $this->locked = $enabled;
    }

    /**
     * Checks if the mine is locked
     * @return bool
     */
    public function isLocked() : bool{
        return $this->locked;
    }

    /**
     * Returns the ore table for this mine
     * @return OreTable
     */
    public function getOreTable() : OreTable{
        return $this->oreTable;
    }

    /**
     * Sets pos1 Vector3 for the mine
     * @param Vector3 $pos1
     */
    public function setPos1(Vector3 $pos1) : void{
        $this->pos1 = $pos1;
        $this->updateBB();
    }

    /**
     * Sets the pos2 Vector3 for the mine
     * @param Vector3 $pos2
     */
    public function setPos2(Vector3 $pos2) : void{
        $this->pos2 = $pos2;
        $this->updateBB();
    }

    /**
     * @param Vector3 $vector3
     *
     * @return bool
     */
    public function setSpawnLocation(Vector3 $vector3) : bool{
        $newSpawn = new Position($vector3->x, $vector3->y, $vector3->z, $this->level);
        if($this->isInMineableArea($newSpawn)){
            return false;
        }
        $this->spawnLocation = $vector3;

        return true;
    }

    /**
     * @return Position
     * @throws \InvalidArgumentException
     */
    public function getSpawnLocation() : \pocketmine\world\Position{
        if(!$this->level instanceof World or !$this->spawnLocation instanceof Vector3){
            throw new \InvalidArgumentException("Mine::getSpawnLocation() called when no valid level or coordinates were set!");
        }
        return new Position($this->spawnLocation->x, $this->spawnLocation->y, $this->spawnLocation->z, $this->level);
    }


    /**
     * Updates the mine pos
     * @return void
     */
    private function updateBB() : void{
        $minX = $this->pos1->x > $this->pos2->x ? $this->pos2->x : $this->pos1->x;
        $minY = $this->pos1->y > $this->pos2->y ? $this->pos2->y : $this->pos1->y;
        $minZ = $this->pos1->z > $this->pos2->z ? $this->pos2->z : $this->pos1->z;
        $maxX = $this->pos1->x < $this->pos2->x ? $this->pos2->x : $this->pos1->x;
        $maxY = $this->pos1->y < $this->pos2->y ? $this->pos2->y : $this->pos1->y;
        $maxZ = $this->pos1->z < $this->pos2->z ? $this->pos2->z : $this->pos1->z;
        if($this->bb === null){
            $this->bb = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        }else{
            $this->bb = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        }
    }

    /**
     * Returns the mine bb
     * @return AxisAlignedBB
     */
    public function getBB() : AxisAlignedBB{
        return $this->bb;
    }

    /**
     * Returns true if the given position is in the level and designated
     * area for ores to be filled.
     *
     * @param Position $pos
     *
     * @return bool
     */
    public function isInMineableArea(Position $pos) : bool{
        if($this->level->getFolderName() !== $pos->getWorld()->getFolderName()){
            return false;
        }
        
        $bb = $this->bb->expandedCopy(1, 1, 1);
        if(!$bb->isVectorInside($pos)){
            return false;
        }

        return true;
    }

    public function resetRemainingBlocks() : void{
        $this->removedBlocks = [];
        $this->remainingBlocks = $this->totalBlocks;
        $this->setLastReset();
    }

    /**
     * If block is in minable area, ensures the block is removed and returns true, else returns false.
     *
     * @param Block $block
     *
     * @return bool
     */
    public function removeOre(Block $block) : bool{
        if(!$block->getPosition()->getWorld()->getFolderName() === $this->level->getFolderName()){
            return false;
        }
        if(!$this->isInMineableArea($block->getPosition())){
            return false;
        }
        if(!isset($this->removedBlocks[(int) $block->getPosition()->getX()][(int) $block->getPosition()->getY()][(int) $block->getPosition()->getZ()])){
            $this->reduceBlockCount();
            $this->removedBlocks[(int) $block->getPosition()->getX()][(int) $block->getPosition()->getY()][(int) $block->getPosition()->getZ()] = true;
        }
        return true;
    }

    /**
     * Reduces the block count
     * @return void
     */
    private function reduceBlockCount() : void{
        $this->remainingBlocks--;
    }

    /**
     * Returns the remaining blocks
     * @return int
     */
    public function getRemainingBlocks() : int{
        return $this->remainingBlocks;
    }

    /**
     * Returns the total blocks
     * @return int
     */
    public function getTotalBlocks() : int{
        return $this->totalBlocks;
    }

    /**
     * Calculates the total blocks from the current bb data.
     * @return void
     */
    private function calculateTotalBlocks() : void{
        $x = (int) $this->bb->maxX - $this->bb->minX;
        $y = (int) $this->bb->maxY - $this->bb->minY;
        $z = (int) $this->bb->maxZ - $this->bb->minZ;
        $this->totalBlocks = (int) ($x * $y * $z);
    }

    /**
     * Returns when the mine is last reset.
     * @return \DateTime
     */
    public function getLastReset() : \DateTime{
        return $this->lastReset;
    }

    /**
     * Set's the last reset date
     * @return void
     */
    private function setLastReset() : void{
        $this->lastReset = new \DateTime();
    }
    
    /**
     * Returns true if player can access this mine
     * @param Player $player
     * @return boolean
     */
    public function hasAccess(Player $player){
        try{
            return $player->hasPermission("mineshaft.break." . strtolower($this->getName()));
        } catch(\InvalidArgumentException $exception){
            return false;
        }
    }
}