<?php

namespace naeng\quests;

use alvin0319\VotifierAPI\event\PlayerVoteEvent;
use Generator;
use kim\present\sqlcore\SqlCore;
use muqsit\invmenu\InvMenuHandler;
use naeng\quests\command\QuestAdminCommand;
use naeng\quests\command\QuestCommand;
use naeng\quests\database\DatabaseManager;
use naeng\quests\info\QuestInfoIntegration;
use naeng\quests\quest\QuestFactory;
use naeng\quests\utils\ItemUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use RoMo\InfoPlugin\InfoPlugin;
use SOFe\AwaitGenerator\Await;

class Quests extends PluginBase implements Listener{

    use SingletonTrait;

    public const PREFIX = '§l§6 • §r§7';

    private DataConnector $database;
    private DatabaseManager $databaseManager;
    private QuestFactory $questFactory;

    public function onLoad() : void{
        self::setInstance($this);
    }

    public function onEnable() : void{
        $this->saveResource("mysql.sql");

        // DB 초기화 (SqlCore 연동)
        if(class_exists(SqlCore::class)){
            $this->database = libasynql::create($this, SqlCore::getSqlConfig(), [
                "mysql" => "mysql.sql"
            ]);
        }else{
            $this->getLogger()->error("SqlCore not found!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // DatabaseManager 초기화
        $this->databaseManager = new DatabaseManager($this->database);

        // 퀘스트 팩토리 초기화
        $this->questFactory = new QuestFactory();

        // 명령어 등록
        $this->getServer()->getCommandMap()->register($this->getName(), new QuestCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new QuestAdminCommand());

        // 이벤트 리스너 등록
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // DB에서 보상 데이터 로드
        $this->loadRewardsFromDb();

        // InvMenu 등록
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        // InfoPlugin 연동
        if(class_exists(InfoPlugin::class)){
            QuestInfoIntegration::register();
            $this->getLogger()->info("InfoPlugin 연동 완료");
        }

        // 초기 날짜 체크
        $this->checkDate();

        // 5분마다 날짜 체크
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            $this->checkDate();
        }), 20 * 60 * 5);
    }

    protected function onDisable() : void{
        if(isset($this->database)){
            $this->database->close();
        }
    }

    /**
     * DB에서 퀘스트별 보상 데이터 로드
     */
    private function loadRewardsFromDb() : void{
        Await::f2c(function() : Generator{
            $rewards = yield from $this->databaseManager->loadAllRewards();

            foreach($rewards as $questId => $itemData){
                $quest = $this->questFactory->getQuest($questId);
                if($quest === null){
                    continue;
                }

                // 여러 아이템 지원 (리스트로 역직렬화)
                try{
                    $items = ItemUtils::deserializeList($itemData);
                    if(count($items) > 0){
                        $quest->setRewardItems($items);
                        $this->getLogger()->debug("Loaded " . count($items) . " reward(s) for quest: {$questId}");
                    }
                }catch(\Exception $e){
                    // 이전 형식 (단일 아이템) 호환
                    $item = ItemUtils::deserialize($itemData);
                    if(!$item->isNull()){
                        $quest->setRewardItems([$item]);
                        $this->getLogger()->debug("Loaded reward for quest: {$questId}");
                    }
                }
            }

            $this->getLogger()->info("Loaded " . count($rewards) . " quest rewards from database");
        });
    }

    public function checkDate() : void{
        $today = date('Y-m-d', time());
        if($this->getConfig()->get('today', '') === $today){
            return;
        }

        $this->getConfig()->set('today', $today);
        $this->getConfig()->save();

        $this->getLogger()->info("§a§l하루가 지나 일일 퀘스트 데이터들이 초기화 됩니다");

        // 일일 퀘스트 리셋
        $this->questFactory->resetDailyQuests();
        $this->databaseManager->resetDailyProgress();
    }

    public function getDatabase() : DataConnector{
        return $this->database;
    }

    public function getDatabaseManager() : DatabaseManager{
        return $this->databaseManager;
    }

    public function getQuestFactory() : QuestFactory{
        return $this->questFactory;
    }

    /**
     * 플레이어 접속 시 클리어 데이터 로드
     */
    public function handlePlayerJoinEvent(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $playerName = strtolower($player->getName());

        Await::f2c(function() use($player, $playerName) : Generator{
            // 클리어 데이터 로드
            $clearedQuests = yield from $this->databaseManager->loadAllCleared($playerName);
            foreach($clearedQuests as $questId => $cleared){
                $quest = $this->questFactory->getQuest($questId);
                if($quest !== null){
                    $quest->setClearedCache($playerName, true);
                }
            }

            // 각 퀘스트의 진행 데이터 로드
            foreach($this->questFactory->getQuests() as $quest){
                $progressData = yield from $this->databaseManager->loadProgress($playerName, $quest->getId());
                foreach($progressData as $missionIndex => $progress){
                    $missions = $quest->getMissions();
                    if(isset($missions[$missionIndex])){
                        $missions[$missionIndex]->setProgress($playerName, $progress, false);
                    }
                }

                // 진행 데이터 로드 후 완료 여부 체크 (이전 세션에서 미완료된 퀘스트 처리)
                if(!$quest->isCleared($playerName) && count($progressData) > 0){
                    $quest->clearCheck($playerName);
                }
            }

            // 데이터 로드 완료 후 스코어보드 업데이트
            if($player->isOnline()){
                QuestInfoIntegration::updateScoreboard($player);
            }
        });
    }

    /**
     * @priority MONITOR
     */
    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
        if($event->isCancelled()){
            return;
        }

        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleBlockBreakEvent($event);
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function handleCommandEvent(CommandEvent $event) : void{
        if($event->isCancelled()){
            return;
        }

        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleCommandEvent($event);
            }
        }
    }

    /**
     * 마인리스트 추천 이벤트 핸들러
     * @priority MONITOR
     */
    public function handleVoteEvent(PlayerVoteEvent $event) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleVoteEvent($event);
            }
        }
    }

    /**
     * 플레이어 퇴장 시 캐시 정리
     */
    public function handlePlayerQuitEvent(PlayerQuitEvent $event) : void{
        QuestInfoIntegration::onPlayerQuit($event->getPlayer());
    }
}
