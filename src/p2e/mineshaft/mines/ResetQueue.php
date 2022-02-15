<?php
declare(strict_types=1);

namespace p2e\mineshaft\mines;



use p2e\mineshaft\MineShaft;
use p2e\mineshaft\tasks\MineResetTask;
use pocketmine\Server;

class ResetQueue{

    /** @var Mine[] */
    private $queue = [];

    /** @var array  */
    private $queueList = [];

    /**
     * Check if a mine is queued
     * @param Mine $mine
     * @return bool
     */
    public function isQueued(Mine $mine) : bool{
        return isset($this->queueList[$mine->getName()]);
    }

    /**
     * Add a mine to the queue
     * @param Mine $mine
     * @retun void
     */
    public function addMine(Mine $mine) : void{
        if(!$this->isQueued($mine)){
            $this->queue[] = $mine;
            $this->queueList[$mine->getName()] = true;
        }
    }

    /**
     * Proccess the next mine
     * @return void
     */
    public function processNext() : void{
        $nextMine = array_shift($this->queue);
        if($nextMine instanceof Mine){
            $protectionEnabled = MineShaft::getProperties()->isEntireWorldProtectionEnabled();
             Server::getInstance()->getAsyncPool()->submitTask(new MineResetTask($nextMine, $protectionEnabled));
            unset($this->queueList[$nextMine->getName()]);
        }
    }
}