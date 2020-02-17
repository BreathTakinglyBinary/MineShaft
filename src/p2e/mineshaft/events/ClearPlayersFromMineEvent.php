<?php
declare(strict_types=1);

namespace p2e\mineshaft\events;


use p2e\mineshaft\mines\Mine;
use p2e\mineshaft\MineShaft;
use pocketmine\level\Position;
use pocketmine\Server;

class ClearPlayersFromMineEvent extends MineEvent{

    /** @var Position */
    private $destination;

    public function __construct(Mine $mine){
        if(MineShaft::getProperties()->isUseServerSpawnEnabled()){
            MineShaft::getInstance()->getLogger()->debug("Setting player destination to server default.");
            $this->destination = Server::getInstance()->getDefaultLevel()->getSpawnLocation();
        }else{
            $this->destination = $mine->getSpawnLocation();
        }
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