<?php
declare(strict_types=1);

namespace p2e\mineshaft;


use DateInterval;

class MineShaftConfiguration{

    public const REFILL_TYPE_PERCENT = 0;

    public const REFILL_TYPE_TIME = 1;

    private $autoRefill = false;

    private $entireWorld = false;

    private $protectionEnabled = false;

    /** @var int */
    private $queueProcessInterval = 10;

    private $refillType = self::REFILL_TYPE_PERCENT;

    private $refillPercentage = 5;

    /** @var DateInterval */
    private $refillInterval;

    /**
     * @param bool $autoRefill
     */
    public function enableAutoRefill(bool $autoRefill) : void{
        $this->autoRefill = $autoRefill;
    }

    /**
     * @return bool
     */
    public function isAutoRefillEnabled() : bool{
        return $this->autoRefill;
    }

    /**
     * @param bool $entireWorld
     */
    public function setEntireWorldProtectionEnabled(bool $entireWorld) : void{
        $this->entireWorld = $entireWorld;
    }

    /**
     * @return bool
     */
    public function isEntireWorldProtectionEnabled() : bool{
        return $this->entireWorld;
    }

    /**
     * @param bool $protectionEnabled
     */
    public function enableProtection(bool $protectionEnabled) : void{
        $this->protectionEnabled = $protectionEnabled;
    }

    /**
     * @return bool
     */
    public function isProtectionEnabled() : bool{
        return $this->protectionEnabled;
    }

    /**
     * Represent number of ticks between each process.
     *
     * @return int
     */
    public function getQueueProcessInterval() : int{
        return $this->queueProcessInterval;
    }

    /**
     * @param int $queueProcessInterval
     */
    public function setQueueProcessInterval(int $queueProcessInterval) : void{
        if(!($queueProcessInterval >= 1)){
            MineShaft::getInstance()->getLogger()->error("Tried to set an invalid value of \"$queueProcessInterval\" for queue process interval.  Must be an integer greater than 0. Using " . $this->queueProcessInterval . " instead.");
        }
        $this->queueProcessInterval = $queueProcessInterval;
    }

    /**
     * @return DateInterval
     */
    public function getRefillInterval() : DateInterval{
        if(!$this->refillInterval instanceof DateInterval){
            MineShaft::getInstance()->getLogger()->debug("Tried to get refill interval before it was set.  Using default of 600 seconds.");
            $this->refillInterval = new DateInterval("PT600S");
        }
        return $this->refillInterval;
    }

    /**
     * @param int $seconds
     */
    public function setRefillInterval(int $seconds) : void{
        $default = 600;
        if(!($seconds >= 60)){
            MineShaft::getInstance()->getLogger()->error("Tried to set an invalid value of \"$seconds\" for refill interval.  Must be an integer of 60 or more. Using $default instead.");
            $seconds = $default;
        }
        $this->refillInterval = new DateInterval("PT" . $seconds . "S");
    }

    /**
     * @return int
     */
    public function getRefillPercentage() : int{
        return $this->refillPercentage;
    }

    /**
     * @param int $refillPercentage
     */
    public function setRefillPercentage(int $refillPercentage) : void{
        if(!($refillPercentage <= 90 and 1 <= $refillPercentage)){
            MineShaft::getInstance()->getLogger()->error("Tried to set an invalid value of \"$refillPercentage\" for refill percentage.  Must be an integer between 1 and 90. Using " . $this->refillPercentage . " instead.");

            return;
        }
        $this->refillPercentage = $refillPercentage;
    }

    /**
     * @return int
     */
    public function getRefillType() : int{
        return $this->refillType;
    }

    /**
     * @param int $refillType
     */
    public function setRefillType(int $refillType) : void{
        $this->refillType = $refillType;
    }
}