<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class Mine{

    /** @var AxisAlignedBB */
    private $bb;

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

    public function __construct(string $name, Level $level, Vector3 $pos1, Vector3 $pos2, OreTable $oreTable){
        $this->name = $name;
        $this->level = $level;
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
        $this->oreTable = $oreTable;
        $this->updateBB();
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

    public function isLocked() : bool {
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
        $minX = $this->pos1->x < $this->pos2->x ? $this->pos2->x : $this->pos1->x;
        $minY = $this->pos1->y < $this->pos2->y ? $this->pos2->y : $this->pos1->y;
        $minZ = $this->pos1->z < $this->pos2->z ? $this->pos2->z : $this->pos1->z;
        $maxX = $this->pos1->x > $this->pos2->x ? $this->pos2->x : $this->pos1->x;
        $maxY = $this->pos1->y > $this->pos2->y ? $this->pos2->y : $this->pos1->y;
        $maxZ = $this->pos1->z > $this->pos2->z ? $this->pos2->z : $this->pos1->z;
        $this->bb->setBounds($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
    }

    public function getBB() : AxisAlignedBB{
        return $this->bb;
    }

    /**
     * Returns true if the given position is in the level and designated
     * area for ores to be filled. This is inteneded for 2 purposes, to
     * check a player's position before refilling the mines and for world
     * protections to determine if a given block is in the mineable area.
     *
     * @param Position $pos
     *
     * @return bool
     */
    public function isInMineableArea(Position $pos) : bool{
        if($this->level->getFolderName() !== $pos->getLevel()->getFolderName()){
            return false;
        }
        if(!$this->bb->isVectorInside($pos)){
            return false;
        }
        return true;
    }
}