<?php
declare(strict_types=1);

namespace p2e\mineshaft;


use p2e\mineshaft\mines\MineManager;
use p2e\mineshaft\tasks\MineManagerHeatbeatTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;

class MineShaft extends PluginBase implements Listener{

    /** @var MineShaftConfiguration */
    private static $properties;

    /** @var MineShaft */
    private static $instance;

    /** @var MineManager */
    private $mineManager;

    public function onEnable() : void{
        self::$instance = $this;
        $this->loadConfig();
        $this->mineManager = new MineManager();
        $this->getScheduler()->scheduleRepeatingTask(new MineManagerHeatbeatTask($this->mineManager), self::$properties->getQueueProcessInterval());
    }

    /**
     * Returns the properties of the plugin
     * @return MineShaftConfiguration
     */
    public static function getProperties() : MineShaftConfiguration{
        return self::$properties;
    }

    /**
     * Get's the instance of the plugin
     * @return MineShaft
     */
    public static function getInstance() : MineShaft{
        return self::$instance;
    }

    /**
     * @return MineManager
     */
    public function getMineManager() : MineManager{
        return $this->mineManager;
    }

    /**
     * Loads the config
     * @throws \RuntimeException
     */
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
            $this->sendinvalidValueWarning("automatic", "true", "refill");
            $automatic = true;
        }
        self::$properties->enableAutoRefill($automatic);

        $type = strtolower($config->getNested("refill.type", ""));
        if($type === "time"){
            self::$properties->setRefillType(MineShaftConfiguration::REFILL_TYPE_TIME);
        }elseif($type !== "percent"){
            $this->sendinvalidValueWarning("type", "percent", "refill");
        }

        if(is_int(($interval = $config->getNested("refill.interval", null)))){
            self::$properties->setRefillInterval($interval);
        }else{
            $this->sendinvalidValueWarning("interval", self::$properties->getRefillInterval()->format("%s seconds"), "refill");
        }

        if(is_int(($percentage = $config->getNested("refill.percentage", null)))){
            self::$properties->setRefillPercentage($percentage);
        }else{
            $this->sendinvalidValueWarning("percentage", (string) self::$properties->getRefillPercentage(), "refill");
        }

        if(is_bool(($useServerSpawn = $config->get("use_server_spawn", null)))){
            self::$properties->setUseServerSpawn($useServerSpawn);
        }else{
            $this->sendinvalidValueWarning("use_server_spawn", self::$properties->isUseServerSpawnEnabled() ? "true" : "false");
        }

        // This value is intentionally left out of the default config as it can cause performance issue if set inappropriately.
        if(is_int(($queueProcessInterval = $config->getNested("global.queue_process_interval", null)))){
            self::$properties->setQueueProcessInterval($queueProcessInterval);
        }
    }

    /**
     * A basic invalid value warning system
     * @param string $property
     * @param string $defaultValue
     * @param string $node
     */
    private function sendinvalidValueWarning(string $property, string $defaultValue, string $node = "") : void{
        if(!$node === ""){
            $node .= " ";
        }
        $this->getLogger()->warning("Found invalid value for " . $node. "setting \"$property\" in config.yml.  Setting to default \"$defaultValue\".");
    }
    
    /**
     * {@inheritDoc}
     * @see \pocketmine\plugin\PluginBase::onCommand()
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() !== "mine") return false;
        if(!$sender instanceof Player){
            $sender->sendMessage("This command can only be executed in-game.");
            return true;
        }
        
        if(count($args) == 0){
            return false;
        }
        
        /** @var \p2e\mineshaft\mines\Mine $mine */
        $mine = $this->mineManager->getMine($args[0]);
        if($mine == NULL){
            $sender->sendMessage("Mineshaft > Mine not found.");
            return true;
        }
        
        $sender->teleport($mine->getSpawnLocation());
        $sender->sendMessage("Mineshaft > I spawned you to ".$mine->getName());
        return true;
    }

}