<?php
declare(strict_types=1);

namespace p2e\mineshaft\tasks;


use p2e\mineshaft\mines\MineManager;
use pocketmine\scheduler\Task;

class MineManagerHeatbeatTask extends Task{

    private $manager;

    public function __construct(MineManager $manager){
        $this->manager = $manager;
    }

    public function onRun(int $currentTick){
        $this->manager->tick();
    }

}