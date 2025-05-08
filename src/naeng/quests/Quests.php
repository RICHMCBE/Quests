<?php

namespace naeng\quests;

use muqsit\invmenu\InvMenuHandler;
use naeng\quests\command\QuestCommand;
use naeng\quests\command\QuestManageCommand;
use naeng\quests\quest\QuestFactory;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;

class Quests extends PluginBase implements Listener{

    use SingletonTrait;

    public const PREFIX = '§l§6 • §r§7';
    private readonly QuestFactory $questFactory;

    /**
     * @var array<string, callable>
     */
    public static array $blockBreakEventQueue = [];

    /**
     * @var array<string, callable>
     */
    public static array $entityDamageByEntityEventQueue = [];

    public function onLoad() : void{
        self::setInstance($this);
    }

    public function onEnable(): void{
        $this->questFactory = new QuestFactory();
        $this->getServer()->getCommandMap()->registerAll($this->getName(), [
            new QuestManageCommand(),
            new QuestCommand()
        ]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        // 초기 체크
        $this->checkDate();

        // 5분마다 checkDate() 실행하는 반복 태스크 등록
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            $this->checkDate();
        }), 20 * 60 * 5); // 20틱 * 60초 * 5분 = 5분마다
    }

    public function checkDate() : void{
        $today = date('d', time());
        if($this->getConfig()->get('today', -1) === $today){
            return;
        }
        $this->getConfig()->set('today', $today);
        $this->getConfig()->save();
        $this->getLogger()->info("§a§l하루가 지나 일일 퀘스트 데이터들이 초기화 됩니다");
        foreach($this->questFactory->getDailyQuests() as $quest){
            $quest->reset();
        }
    }



    public function onDisable() : void{
        $this->questFactory->save();
    }

    public function getQuestFactory() : QuestFactory{
        return $this->questFactory;
    }

    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
        $playerName = $event->getPlayer()->getName();
        if(isset(self::$blockBreakEventQueue[$playerName])){
            (self::$blockBreakEventQueue[$playerName])($event);
            unset(self::$blockBreakEventQueue[$playerName]);
        }
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleBlockBreakEvent($event);
            }
        }
    }

    public function handlePlayerChatEvent(PlayerChatEvent $event) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handlePlayerChatEvent($event);
            }
        }
    }

    public function handleCommandEvent(CommandEvent $event) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleCommandEvent($event);
            }
        }
    }

    /**
     * @prioirty MONITOR
     * @ignoreCancelled true
     */
    public function handleEntityDamageByEntityEvent(EntityDamageByEntityEvent $event) : void{
        $damagerName = $event->getDamager()->getName();
        if(isset(self::$entityDamageByEntityEventQueue[$damagerName])){
            (self::$entityDamageByEntityEventQueue[$damagerName])($event);
            unset(self::$entityDamageByEntityEventQueue[$damagerName]);
        }

        if($event->isCancelled()){
            return;
        }

        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleEntityDamageByEntityEvent($event);
            }
        }
    }

    public function handleEntityDeathEvent(EntityDeathEvent $event) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleEntityDeathEvent($event);
            }
        }
    }

}