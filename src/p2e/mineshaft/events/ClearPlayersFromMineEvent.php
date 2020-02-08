<?php
declare(strict_types=1);

namespace p2e\mineshaft\events;


use p2e\mineshaft\mines\Mine;
use pocketmine\level\Position;

class ClearPlayersFromMineEvent extends MineEvent{

    /** @var Position */
    private $destination;

    public function __construct(Mine $mine, Position $destination){
        $this->destination = $destination;
        parent::__construct($mine);
    }

    /**
     * @return Position
     */
    public function getDestination() : Position{
        return $this->destination;
    }

    /**
     * @param Position $destination
     */
    public function setDestination(Position $destination) : void{
        $this->destination = $destination;
    }

}