<?php
declare(strict_types=1);

namespace p2e\mineshaft\events;


use p2e\mineshaft\mines\Mine;
use pocketmine\event\Event;

abstract class MineEvent extends Event{

    /** @var Mine */
    private $mine;

    public function __construct(Mine $mine){
        $this->mine = $mine;
    }

    /**
     * @return Mine
     */
    public function getMine() : Mine{
        return $this->mine;
    }

}