<?php
declare(strict_types=1);

namespace p2e\mineshaft;


class MineShaftConfiguration{

    public const REFILL_TYPE_PERCENT = 0;

    public const REFILL_TYPE_TIME = 1;

    private $autoRefill = false;

    private $entireWorld = false;

    private $protectionEnabled = false;

    private $refillType = self::REFILL_TYPE_PERCENT;

    private $refillPercentage = 5;

    private $refillInterval = 600;

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
     * @return int
     */
    public function getRefillInterval() : int{
        return $this->refillInterval;
    }

    /**
     * @param int $refillInterval
     */
    public function setRefillInterval(int $refillInterval) : void{
        if(!($refillInterval >= 60)){
            MineShaft::getInstance()->getLogger()->error("Tried to set an invalid value of \"$refillInterval\" for refill interval.  Must be an integer of 60 or more. Using " . $this->refillInterval . " instead.");
            return;
        }
        $this->refillInterval = $refillInterval;
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