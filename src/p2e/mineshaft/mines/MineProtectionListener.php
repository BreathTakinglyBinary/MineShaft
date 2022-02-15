<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;


use InvalidArgumentException;
use p2e\mineshaft\MineShaft;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\player\Player;

class MineProtectionListener extends MineListener implements Listener{

    /** @var string */
    const ACTION_BREAK = "break";
    /** @var string */
    const ACTION_PLACE = "place";

    /**
     * @param BlockBreakEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockBreak(BlockBreakEvent $event) : void{
        if(!$this->isActionAllowable($event->getPlayer(), $event->getBlock()->getPosition(), self::ACTION_BREAK)){
            $event->cancel();
        }
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockPlace(BlockPlaceEvent $event) : void{
        if(!$this->isActionAllowable($event->getPlayer(), $event->getBlock()->getPosition(), self::ACTION_PLACE)){
            $event->cancel();
        }
    }

    /**
     * @param Player   $player
     * @param Position $pos
     * @param string   $action
     *
     * @return bool
     */
    private function isActionAllowable(Player $player, Position $pos, string $action) : bool{
        if(MineShaft::getProperties()->isProtectionEnabled() == true){
            return true;
        }
        $level = $pos->getWorld();
        if(!$level instanceof World){
            MineShaft::getInstance()->getLogger()->error("MineProtectionListener:isActionAllowable() found block with no Level data!");
            return false;
        }
        if($level->getDisplayName() !== $this->mine->getWorld()->getDisplayName()){
            return true;
        }
        $hasPermission = $this->testNodePerm($player, $action);
        $bypass = $this->testBypassPerm($player);
        $isMinableArea = $this->mine->isInMineableArea($pos);
        if($isMinableArea and ($hasPermission or $bypass)){
            return true;
        }
        if(MineShaft::getProperties()->isEntireWorldProtectionEnabled() and $bypass){
            return true;
        }
        return false;
    }

    private function testNodePerm(Player $player, string $node) : bool{
        try{
            return $player->hasPermission("mineshaft.$node." . strtolower($this->mine->getName()));
        } catch(\InvalidArgumentException $exception){
            return false;
        }
    }

    private function testBypassPerm(Player $player) : bool{
        return $this->testNodePerm($player, "bypass");
    }

}