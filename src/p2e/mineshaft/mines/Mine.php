<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use p2e\mineshaft\MineShaft;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class Mine{

    /** @var AxisAlignedBB */
    private $bb;

    /** @var \DateTime */
    private $lastReset;

    /** @var Level */
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

    /** @var int */
    private $totalBlocks;

    public function __construct(string $name, Level $level, Vector3 $pos1, Vector3 $pos2, array $ores){
        $this->name = $name;
        $this->level = $level;
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
        $this->updateBB();

        $this->oreTable = new OreTable($ores, $this->bb);
        $this->calculateTotalBlocks();
        $this->resetRemainingBlocks();
        $this->setLastReset();
    }

    public function getName() : string{
        return $this->name;
    }

    public function getLevel() : Level{
        return $this->level;
    }

    public function setLocked(bool $enabled = true) : void{
        $this->locked = $enabled;
    }

    public function isLocked() : bool{
        return $this->locked;
    }

    public function getOreTable() : OreTable{
        return $this->oreTable;
    }

    public function setPos1(Vector3 $pos1) : void{
        $this->pos1 = $pos1;
        $this->updateBB();
    }

    public function setPos2(Vector3 $pos2) : void{
        $this->pos2 = $pos2;
        $this->updateBB();
    }


    private function updateBB() : void{
        $minX = $this->pos1->x > $this->pos2->x ? $this->pos2->x : $this->pos1->x;
        $minY = $this->pos1->y > $this->pos2->y ? $this->pos2->y : $this->pos1->y;
        $minZ = $this->pos1->z > $this->pos2->z ? $this->pos2->z : $this->pos1->z;
        $maxX = $this->pos1->x < $this->pos2->x ? $this->pos2->x : $this->pos1->x;
        $maxY = $this->pos1->y < $this->pos2->y ? $this->pos2->y : $this->pos1->y;
        $maxZ = $this->pos1->z < $this->pos2->z ? $this->pos2->z : $this->pos1->z;
        if($this->bb === null){
            MineShaft::getInstance()->getLogger()->debug("Creating new AABB with $minX, $minY, $minZ, $maxX, $maxY, $maxZ");
            $this->bb = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        }else{
            $this->bb->setBounds($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        }
    }

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
        if($this->level->getFolderName() !== $pos->getLevel()->getFolderName()){
            MineShaft::getInstance()->getLogger()->debug("Mine::isInMineableArea(): Folder name is not a match");
            return false;
        }
        $bb = $this->bb->expandedCopy(1, 1, 1);
        if(!$bb->isVectorInside($pos)){
            MineShaft::getInstance()->getLogger()->debug("Mine::isInMineableArea(): not inside the bounding box");
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
        if(!$block->getLevel()->getName() === $this->level->getName()){
            return false;
        }
        if(!$this->isInMineableArea($block)){
            return false;
        }
        if(!isset($this->removedBlocks[(int) $block->x][(int) $block->z][(int) $block->z])){
            $this->reduceBlockCount();
            $this->removedBlocks[(int) $block->x][(int) $block->z][(int) $block->z] = true;
        }
        return true;
    }

    private function reduceBlockCount() : void{
        $this->remainingBlocks--;
    }

    public function getRemainingBlocks() : int{
        return $this->remainingBlocks;
    }

    public function getTotalBlocks() : int{
        return $this->totalBlocks;
    }

    private function calculateTotalBlocks() : void{
        $x = (int) $this->bb->maxX - $this->bb->minX;
        $y = (int) $this->bb->maxY - $this->bb->minY;
        $z = (int) $this->bb->maxZ - $this->bb->minZ;
        $this->totalBlocks = (int) ($x * $y * $z);
    }

    public function getLastReset() : \DateTime{
        return $this->lastReset;
    }

    private function setLastReset() : void{
        $this->lastReset = new \DateTime();
    }
}