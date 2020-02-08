<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;



use p2e\mineshaft\MineShaft;
use p2e\mineshaft\tasks\MineResetTask;

class ResetQueue{

    /** @var Mine[] */
    private $queue;

    public function addMine(Mine $mine) : void{
        $this->queue[] = $mine;
    }

    public function processNext() : void{
        $nextMine = array_shift($this->queue);
        if($nextMine instanceof Mine){
            $protectionEnabled = MineShaft::getProperties()->isEntireWorldProtectionEnabled();
            MineShaft::getInstance()->getScheduler()->scheduleTask(new MineResetTask($nextMine, $protectionEnabled));
        }
    }
}