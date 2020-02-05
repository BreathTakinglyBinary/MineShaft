<?php
declare(strict_types=1);

namespace p2e\mineshaft;


use pocketmine\plugin\PluginBase;

class MineShaft extends PluginBase{

    /** @var MineShaftConfiguration */
    public static $properties;

    /** @var MineShaft */
    private static $instance;

    public function onEnable(){
        self::$instance = $this;
        $this->loadConfig();
    }

    public static function getProperties() : MineShaftConfiguration{
        return self::$properties;
    }

    public static function getInstance() : MineShaft{
        return self::$instance;
    }

    private function loadConfig() : void{
        if(self::$properties === null){
            self::$properties = new MineShaftConfiguration();
        }
        $config = $this->getConfig();

        // Get protection settings
        if(($enabled = $config->getNested("protection.enabled", null)) === null){
            throw new \RuntimeException("Unable to find necessary protection setting \"enabled\" in config.yml");
        }elseif(!is_bool($enabled)){
            throw new \RuntimeException("Found invalid value for protection setting \"enabled\" in config.yml");
        }else{
            self::$properties->enableProtection($enabled);
        }

        if(($entireWorld = $config->getNested("protection.entire_world", null)) === null){
            throw new \RuntimeException("Unable to find necessary protection setting \"entire_world\" in config.yml");
        }elseif(!is_bool($entireWorld)){
            throw new \RuntimeException("Found invalid value for protection setting \"entire_world\" in config.yml");
        }else{
            self::$properties->setEntireWorldProtectionEnabled($entireWorld);
        }

        if(!is_bool(($automatic = $config->getNested("refill.automatic", null)))){
            $this->getLogger()->warning("Found invalid value for refill setting \"automatic\" in config.yml.  Setting to default \"true\"");
            $automatic = true;
        }
        self::$properties->enableAutoRefill($automatic);

        $type = strtolower($config->getNested("refill.type", ""));
        if($type === "time"){
            self::$properties->setRefillType(MineShaftConfiguration::REFILL_TYPE_TIME);
        }elseif($type !== "percent"){
            $this->getLogger()->warning("Found invalid value for refill setting \"type\" in config.yml.  Setting to default \"percent\"");
        }

        if(is_int(($interval = $config->getNested("refill.interval", null)))){
            self::$properties->setRefillInterval($interval);
        }else{
            $this->getLogger()->warning("Found invalid value for refill setting \"interval\" in config.yml.  Setting to default \"" . self::$properties->getRefillInterval() . "\".");
        }

        if(is_int(($percentage = $config->getNested("refill.percentage", null)))){
            self::$properties->setRefillPercentage($percentage);
        }else{
            $this->getLogger()->warning("Found invalid value for refill setting \"percentage\" in config.yml.  Setting to default \"" . self::$properties->getRefillPercentage() . "\".");
        }
    }

}