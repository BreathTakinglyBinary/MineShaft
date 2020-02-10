<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;



use p2e\mineshaft\MineShaft;
use p2e\mineshaft\tasks\MineResetTask;

class ResetQueue{

    /** @var Mine[] */
    private $queue;

    /** @var array  */
    private $queueList = [];

    public function isQueued(Mine $mine) : bool{
        return isset($this->queueList[$mine->getName()]);
}

    public function addMine(Mine $mine) : void{
        if(!$this->isQueued($mine)){
            $this->queue[] = $mine;
            $this->queueList[$mine->getName()] = true;
        }
    }

    public function processNext() : void{
        $nextMine = array_shift($this->queue);
        if($nextMine instanceof Mine){
            $protectionEnabled = MineShaft::getProperties()->isEntireWorldProtectionEnabled();
            MineShaft::getInstance()->getScheduler()->scheduleTask(new MineResetTask($nextMine, $protectionEnabled));
            unset($this->queueList[$nextMine->getName()]);
        }
    }
}