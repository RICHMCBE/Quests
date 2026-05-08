<?php

namespace naeng\quests;

use alvin0319\VotifierAPI\event\PlayerVoteEvent;
use Generator;
use kim\present\sqlcore\SqlCore;
use muqsit\invmenu\InvMenuHandler;
use naeng\quests\command\QuestAdminCommand;
use naeng\quests\command\QuestCommand;
use naeng\quests\database\DatabaseManager;
use naeng\quests\listener\FishQuestListener;
use naeng\PlayingTime\PlayingTime;
use naeng\quests\quest\missions\defaults\PlayTimeMission;
use naeng\quests\quest\missions\defaults\AttendanceClaimMission;
use naeng\quests\quest\missions\defaults\DivingMineAcquireMission;
use naeng\quests\quest\missions\defaults\ExchangeBuyMission;
use naeng\quests\quest\missions\defaults\RankUpgradeMission;
use naeng\quests\quest\missions\defaults\ShopSellMission;
use naeng\quests\quest\missions\defaults\ToolUpgradeMission;
use naeng\quests\quest\QuestFactory;
use naeng\quests\quest\QuestRegistry;
use naeng\quests\utils\ItemUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
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

        // FishPlugin 연동
        if($this->getServer()->getPluginManager()->getPlugin("FishPlugin") !== null){
            $this->getServer()->getPluginManager()->registerEvents(new FishQuestListener(), $this);
            foreach(QuestRegistry::getFishQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            foreach(QuestRegistry::getFishGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("FishPlugin 연동 완료 - 낚시 퀘스트 활성화");
        }

        // DivingMine 연동
        if($this->getServer()->getPluginManager()->getPlugin("DivingMine") !== null){
            foreach(QuestRegistry::getDivingMineGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("DivingMine 연동 완료 - 잠수광산 가이드 퀘스트 활성화");
        }

        // NeighborPlugin 연동
        if($this->getServer()->getPluginManager()->getPlugin("NeighborPlugin") !== null){
            foreach(QuestRegistry::getNeighborhoodGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("NeighborPlugin 연동 완료 - 길드 가이드 퀘스트 활성화");
        }

        // AttendanceCheck 연동
        if($this->getServer()->getPluginManager()->getPlugin("AttendanceCheck") !== null){
            foreach(QuestRegistry::getAttendanceGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("AttendanceCheck 연동 완료 - 출석 가이드 퀘스트 활성화");
        }

        // RankPrefix 연동
        if($this->getServer()->getPluginManager()->getPlugin("RankPrefix") !== null){
            foreach(QuestRegistry::getRankGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("RankPrefix 연동 완료 - 랭크 가이드 퀘스트 활성화");
        }

        // Warehouse 연동
        if($this->getServer()->getPluginManager()->getPlugin("Warehouse") !== null){
            foreach(QuestRegistry::getWarehouseGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("Warehouse 연동 완료 - 창고 가이드 퀘스트 활성화");
        }

        // UserExchange 연동
        if($this->getServer()->getPluginManager()->getPlugin("UserExchange") !== null){
            foreach(QuestRegistry::getExchangeGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("UserExchange 연동 완료 - 거래소 가이드 퀘스트 활성화");
        }

        // NeighborhoodShop 연동
        if($this->getServer()->getPluginManager()->getPlugin("NeighborhoodShop") !== null){
            foreach(QuestRegistry::getNeighborhoodShopGuideQuests() as $quest){
                $this->questFactory->addQuest($quest);
            }
            $this->getLogger()->info("NeighborhoodShop 연동 완료 - 길드상점 가이드 퀘스트 활성화");
        }

        // DB에서 보상 데이터 로드
        $this->loadRewardsFromDb();

        // InvMenu 등록
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        // 초기 날짜 체크
        $this->checkDate();

        // 5분마다 날짜 체크
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            $this->checkDate();
        }), 20 * 60 * 5);

        // 1분마다 PlayingTime 플러그인에서 오늘 접속 시간을 읽어 진행도 갱신
        if($this->getServer()->getPluginManager()->getPlugin("PlayingTime") !== null){
            $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
                foreach($this->getServer()->getOnlinePlayers() as $player){
                    foreach($this->questFactory->getDailyQuests() as $quest){
                        foreach($quest->getMissions() as $mission){
                            if(!($mission instanceof PlayTimeMission) || $mission->isCleared($player)){
                                continue;
                            }
                            Await::f2c(function() use($player, $mission) : Generator{
                                $session = yield from PlayingTime::getInstance()->getSession($player->getXuid());
                                $todaySeconds = yield from $session->getToday();
                                if($player->isOnline()){
                                    $mission->updateFromPlayingTime($player, $todaySeconds);
                                }
                            });
                        }
                    }
                }
            }), 20 * 60);
            $this->getLogger()->info("PlayingTime 연동 완료 - 접속시간 퀘스트 활성화");
        }else{
            $this->getLogger()->warning("PlayingTime 플러그인을 찾을 수 없습니다 - 접속시간 미션이 비활성화됩니다");
        }
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

        // 일일 퀘스트 미션 재생성 (랜덤 미션 새로 선택)
        $this->questFactory->rebuildDailyQuests();
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

    // ─────────────────────────────────────────────────────────────────
    // 외부 플러그인 연동 트리거
    // ─────────────────────────────────────────────────────────────────

    /**
     * 상점 플러그인에서 판매 완료 시 호출
     * 예) Quests::getInstance()->handleShopSell($player, "광물상점");
     */
    public function handleShopSell(Player $player, string $shopId) : void{
        $this->getLogger()->info("[ShopSell] player={$player->getName()}, shopId=\"{$shopId}\"");
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof ShopSellMission && str_contains($shopId, $mission->getShopId())){
                    $mission->handleSell($player);
                }
            }
        }
    }

    /**
     * DivingMine에서 아이템 획득 시 호출
     * 예) Quests::getInstance()->handleDivingMineAcquire($player);
     */
    public function handleDivingMineAcquire(Player $player) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof DivingMineAcquireMission){
                    $mission->handleAcquire($player);
                }
            }
        }
    }

    /**
     * AttendanceCheck에서 출석 완료 시 호출
     * 예) Quests::getInstance()->handleAttendanceClaim($player);
     */
    public function handleAttendanceClaim(Player $player) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof AttendanceClaimMission){
                    $mission->handleClaim($player);
                }
            }
        }
    }

    /**
     * RankPrefix에서 랭크 업그레이드 완료 시 호출
     * 예) Quests::getInstance()->handleRankUpgrade($player);
     */
    public function handleRankUpgrade(Player $player) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof RankUpgradeMission){
                    $mission->handleUpgrade($player);
                }
            }
        }
    }

    /**
     * UserExchange에서 구매 완료 시 호출
     * 예) Quests::getInstance()->handleExchangeBuy($player);
     */
    public function handleExchangeBuy(Player $player) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof ExchangeBuyMission){
                    $mission->handleBuy($player);
                }
            }
        }
    }

    /**
     * ToolCore에서 도구 강화 완료 시 호출
     * 예) Quests::getInstance()->handleToolUpgrade($player);
     */
    public function handleToolUpgrade(Player $player) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof ToolUpgradeMission){
                    $mission->handleUpgrade($player);
                }
            }
        }
    }

    /**
     * 관리자용 가이드 퀘스트 강제 클리어
     *
     * @return Generator<int>
     */
    public function forceClearGuideQuests(Player $player, string $selection = "current") : Generator{
        $playerName = strtolower($player->getName());
        $clearedQuests = yield from $this->databaseManager->loadAllCleared($playerName);

        foreach($clearedQuests as $questId => $cleared){
            $quest = $this->questFactory->getQuest($questId);
            if($quest !== null){
                $quest->setClearedCache($playerName, true);
            }
        }

        $targets = [];
        $normalizedSelection = strtolower($selection);

        if($normalizedSelection === "current" || $normalizedSelection === "현재"){
            $quest = $this->questFactory->getCurrentGuideQuest($player);
            if($quest !== null){
                $targets[] = $quest;
            }
        }elseif($normalizedSelection === "all" || $normalizedSelection === "전체"){
            $targets = $this->questFactory->getGuideQuests();
        }else{
            $quest = $this->questFactory->getQuest($selection);
            if($quest !== null && $quest->getType() === $quest::TYPE_GUIDE){
                $targets[] = $quest;
            }
        }

        $isBulkSelection = $normalizedSelection === "all" || $normalizedSelection === "전체";
        $clearedCount = 0;
        foreach($targets as $quest){
            if($quest->isCleared($playerName)){
                continue;
            }

            $quest->clear($player, !$isBulkSelection);
            $clearedCount++;
        }

        if($isBulkSelection && $clearedCount > 0 && $player->isConnected()){
            $this->sendQuestNotification($player);
        }

        return $clearedCount;
    }

    // ─────────────────────────────────────────────────────────────────
    // 이벤트 핸들러
    // ─────────────────────────────────────────────────────────────────

    /**
     * 플레이어 접속 시 클리어 데이터 로드
     * @priority MONITOR
     */
    public function handlePlayerJoinEvent(PlayerJoinEvent $event) : void{
        $player     = $event->getPlayer();
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

                // 진행 데이터 로드 후 완료 여부 체크
                if(!$quest->isCleared($playerName) && count($progressData) > 0){
                    $quest->clearCheck($playerName);
                }
            }

            // 데이터 로드 완료 후 퀘스트 정보 표시 (타이틀)
            if($player->isOnline()){
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player) : void{
                    if($player->isOnline()){
                        $this->sendQuestNotification($player);
                    }
                }), 20);
            }
        });
    }

    /**
     * @priority MONITOR
     */
    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleBlockBreakEvent($event);
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function handleBlockPlaceEvent(BlockPlaceEvent $event) : void{
        if($event->isCancelled()){
            return;
        }

        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleBlockPlaceEvent($event);
            }
        }
    }

    /**
     * @priority LOWEST
     */
    public function handleCommandEvent(CommandEvent $event) : void{
        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleCommandEvent($event);
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function handleChatEvent(PlayerChatEvent $event) : void{
        if($event->isCancelled()){
            return;
        }

        foreach($this->questFactory->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                $mission->handleChatEvent($event);
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
     * 플레이어 퇴장 시 타이틀 제거
     */
    public function handlePlayerQuitEvent(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        if($player->isOnline()){
            $player->sendTitle("§A§D", "");
        }
    }

    /**
     * 가이드 퀘스트 알림을 화면 오른쪽에 표시
     */
    public function sendQuestNotification(Player $player) : void{
        $quest = $this->questFactory->getCurrentGuideQuest($player);

        // 모든 가이드 퀘스트 완료 시 타이틀 제거
        if($quest === null){
            $player->sendTitle("§A§D", "");
            return;
        }

        // 퀘스트 정보 생성
        $stage = $this->questFactory->getCurrentQuestStage($player);
        $title = "§e§l{$stage}. {$quest->getDisplayName()}";

        $titleLines    = [];
        $subtitleLines = [];
        foreach($quest->getMissions() as $mission){
            $isCleared = $mission->isCleared($player);
            $progress  = $mission->getProgress($player) ?? 0;
            $info      = $mission->getInformation();

            // 타겟 수 가져오기
            $target = 1;
            if(method_exists($mission, 'getCount')){
                $count = $mission->getCount();
                if(is_int($count) && $count > 0){
                    $target = $count;
                }
            }

            $checkMark     = $isCleared ? "§a✓" : "§7○";
            $color         = $isCleared ? "§a" : "§f";
            $progressColor = $isCleared ? "§a" : "§7";

            $titleLines[]    = "{$checkMark} {$color}{$info}";
            $subtitleLines[] = "{$progressColor}§l({$progress}/{$target})";
        }

        // 타이틀과 서브타이틀 전송
        $message  = $title . "\n" . implode("\n", $titleLines);
        $subtitle = implode(" ", $subtitleLines);
        $player->sendTitle("§A§C{$message}", "§A§D{$subtitle}");
    }
}
