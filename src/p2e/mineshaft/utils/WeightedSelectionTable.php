<?php
declare(strict_types=1);

namespace p2e\mineshaft\utils;


class WeightedSelectionTable{

    /** @var string */
    public const INDEX_ENTRY = "entry";
    /** @var string */
    public const INDEX_WEIGHT = "weight";


    /** @var array[] Values consist of arrays that follow this format ["entry" => mixed, "weight" => int] */
    private $entries = [];

    /** @var array Sorted lookup array */
    private $lookupTable = [];

    /** @var int */
    private $totalWeight = 0;

        /**
         * Returns the function for the random entry.
         * @return mixed|NULL
         */
    public function getRandBypass(){
        return $this->getRandomEntry();
    }
    
    /**
     * Randomly select one of the elements based on their weights. Optimized for a large number of elements.
     *
     * @param array $lookup Sorted lookup array
     * @param int   $total_weight Sum of all weights
     *
     * @return mixed|null Selected element
     */
    public function getRandomEntry(){
        if(empty($this->entries)){
            return null;
        }
        if($this->lookupTable === null){
            $this->buildLookupTable();
        }

        $selectedWeightPoint = $this->searchLookupTable(random_int(0, $this->totalWeight));

        return $this->entries[$selectedWeightPoint][self::INDEX_ENTRY];
    }

    /**
     * Adds an entry to return with getRandomEntry(). $weight must be a positive integer.
     *
     * @param mixed $entry
     * @param int   $weight
     */
    public function addWeightedEntry($entry, int $weight) : void{
        if($weight < 1){
            throw new \InvalidArgumentException("LootTable::addWeightedEntry called with non positive integer \"$weight\"");
        }
        $this->entries[] = [
            self::INDEX_ENTRY => $entry,
            self::INDEX_WEIGHT => $weight
        ];
        $this->buildLookupTable();
    }


    /**
     * Clears the entries and lookupTable arrays and sets totalWeight to 0.
     */
    public function reset() : void{
        $this->entries = [];
        $this->lookupTable = [];
        $this->totalWeight = 0;
    }

    /**
     * Build the lookup array to use with binary search
     */
    private function buildLookupTable(){
        if(empty($this->entries)){
            throw new \RuntimeException("WeightedSelectionTable::calculateLookups() called with no entries");
        }
        $this->lookupTable = [];
        $this->totalWeight = 0;

        $totalWeightEntries = count($this->entries);
        for($i = 0; $i < $totalWeightEntries; $i++){
            $this->totalWeight += $this->entries[$i][self::INDEX_WEIGHT];
            $this->lookupTable[$i] = $this->totalWeight;
        }
    }

    /**
     * Search a sorted array for a number. Returns the item's index if found. Otherwise
     * returns the position where it should be inserted, or count($this->lookupTable) - 1
     * if the $needle is higher than every element in the array.
     *
     * @param int $needle
     *
     * @return int
     */
    private function searchLookupTable(int $needle) : int{
        $high = count($this->lookupTable) - 1;

        if($high < 0){
            return 0;
        }
        $low = 0;

        while($low < $high){
            $probe = (int) (($high + $low) / 2);
            if($this->lookupTable[$probe] < $needle){
                $low = $probe + 1;
            }else if($this->lookupTable[$probe] > $needle){
                $high = $probe - 1;
            }else{
                return $probe;
            }
        }

        if($low !== $high){
            return $probe;
        }

        if($this->lookupTable[$low] >= $needle){
            return $low;
        }

        return $low + 1;
    }

}