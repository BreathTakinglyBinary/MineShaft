<?php
declare(strict_types=1);

namespace p2e\mineshaft\events;


use p2e\mineshaft\mines\Mine;
use p2e\mineshaft\MineShaft;
use pocketmine\world\Position;
use pocketmine\Server;

class ClearPlayersFromMineEvent extends MineEvent{

    /** @var Position */
    private $destination;

    public function __construct(Mine $mine){
        if(MineShaft::getProperties()->isUseServerSpawnEnabled()){
            MineShaft::getInstance()->getLogger()->debug("Setting player destination to server default.");
            $this->destination = Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
        }else{
            $this->destination = $mine->getSpawnLocation();
        }
        parent::__construct($mine);
    }

    /**
     * Returns the position of the spawn location
     * @return Position
     */
    public function getDestination() : Position{
        return $this->destination;
    }

    /**
     * Set's the destination of the spawn location
     * @param Position $destination
     */
    public function setDestination(Position $destination) : void{
        $this->destination = $destination;
    }

}