<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use p2e\mineshaft\utils\WeightedSelectionTable;
use pocketmine\block\BlockFactory;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class OreTable extends WeightedSelectionTable{

    /** @var array */
    private $ores;

    /** @var array Block[] */
    private $oreMap = [];

    public function __construct(array $ores){
        $this->setOres($ores);
    }


    public function addOre(int $id, int $meta, int $weight) : void{
        $this->ores[$id][$meta] = $weight;
        $block = BlockFactory::get($id, $meta);
        $this->addWeightedEntry($block, $weight);
    }

    public function removeOre(int $id, int $meta) : void{
        unset($this->ores[$id][$meta]);
        $this->updateEntries();
    }

    public function hasOre(int $id, int $meta) : bool{
        return isset($this->ores[$id][$meta]);
    }

    /**
     * Each index in the $ores array must be an integer array with 3 entries
     *
     * ["id" => int, "meta" => int, "weight" => int]
     *
     * @param array[] $ores
     *
     * @throws \InvalidArgumentException
     */
    public function setOres(array $ores) : void{
        if(empty($ores)){
            throw new \InvalidArgumentException("OreTable:setOres called with empty ores array!");
        }
        $this->ores = [];
        $this->oreMap = [];
        foreach($ores as $ore){
            if(!isset($ore["id"]) or !isset($ore["meta"]) or !isset($ore["weight"])){
                throw new \InvalidArgumentException("Ores array passed to OreTable:setOres contains an invalid structure!");
            }

        }
    }

    private function updateEntries() : void{
        $this->reset();
        foreach($this->ores as $id => $values){
            foreach($values as $meta => $weight){
                $block = BlockFactory::get($id, $meta);
                $this->addWeightedEntry($block, $weight);
            }
        }
    }


    public function updateOreMap(AxisAlignedBB $bb) : void{
        for($x = $bb->minX; $bb->maxX > $x; $x++){
            for($y = $bb->minY; $bb->maxY > $y; $y++){
                for($z = $bb->minZ; $bb->maxZ > $z; $z++){
                    $this->oreMap[] = [new Vector3($x, $y, $z), $this->getRandomEntry()];
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getOreMap() : array{
        return $this->oreMap;
    }

}