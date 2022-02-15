<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use p2e\mineshaft\MineShaft;
use p2e\mineshaft\MineShaftConfiguration;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

class MineStatusListener extends MineListener implements Listener{

    /** @var MineManager */
    private $manager;

    public function __construct(Mine $mine, MineManager $manager){
        $this->manager = $manager;
        parent::__construct($mine);
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority MONITOR
     */
    public function onBlockBreak(BlockBreakEvent $event) : void{
        if($event->isCancelled()){
            return;
        }
        $block = $event->getBlock();
        if(!$this->mine->isInMineableArea($block->getPosition())){
            return;
        }

        $this->mine->removeOre($block);
        if(!MineShaft::getProperties()->isAutoRefillEnabled()){
            return;
        }

        if(!MineShaft::getProperties()->getRefillType() === MineShaftConfiguration::REFILL_TYPE_PERCENT){
            return;
        }

        if($this->manager->getResetQueue()->isQueued($this->mine)){
            return;
        }

        $remainingPercentage = floor(($this->mine->getRemainingBlocks() / $this->mine->getTotalBlocks()) * 100);
        if($remainingPercentage <= MineShaft::getProperties()->getRefillPercentage()){
            $this->manager->getResetQueue()->addMine($this->mine);
        }
    }

    /**
     * @param EntityTeleportEvent $event
     *
     * @priority HIGHEST
     */
    public function onTeleport(EntityTeleportEvent $event) : void{
        if($event->isCancelled()){
            return;
        }
        if(!$this->mine->isLocked()){
            return;
        }
        if($this->mine->isInMineableArea($event->getTo())){
            $event->cancel();
        }

    }

    /**
     * @param PlayerMoveEvent $event
     * @priority HIGHEST
     */
    public function onPlayerMove(PlayerMoveEvent $event) : void{
        if($event->isCancelled()){
            return;
        }
        if(!$this->mine->isLocked()){
            return;
        }
        if($this->mine->isInMineableArea($event->getTo())){
            $event->cancel();
        }
    }

}