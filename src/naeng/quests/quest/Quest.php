<?php

namespace naeng\quests\quest;

use naeng\MailCore\data\MailInfo;
use naeng\MailCore\MailCore;
use naeng\quests\quest\missions\Mission;
use naeng\quests\Quests;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use RoMo\XuidCore\XuidCore;
use SOFe\AwaitGenerator\Await;

class Quest{

    public const TYPE_DAILY  = 0;
    public const TYPE_NORMAL = 1;
    public const TYPE_GUIDE = 2;

    /** @var array<string, bool> 메모리 캐시: 클리어 여부 */
    private array $clearedCache = [];

    /**
     * @param Mission[] $missions
     * @param Item[] $rewardItems
     */
    public function __construct(
        private readonly string $id,
        private readonly string $displayName,
        private readonly int    $type,
        private array           $missions = [],
        private array           $rewardItems = [],
        private int             $rewardIslandProgress = 0
    ){
    }

    public function getId() : string{
        return $this->id;
    }

    public function getDisplayName() : string{
        return $this->displayName;
    }

    public function getType() : int{
        return $this->type;
    }

    /**
     * @return Mission[]
     */
    public function getMissions() : array{
        return $this->missions;
    }

    public function addMission(Mission $mission) : void{
        $mission->setQuest($this);
        $mission->setIndex(count($this->missions));
        $this->missions[] = $mission;
    }

    public function getRewardItems() : array{
        return $this->rewardItems;
    }

    public function setRewardItems(array $items) : void{
        $this->rewardItems = $items;
    }

    public function getRewardIslandProgress() : int{
        return $this->rewardIslandProgress;
    }

    public function setRewardIslandProgress(int $islandProgress) : void{
        $this->rewardIslandProgress = $islandProgress;
    }

    public function giveUp(Player|string $player) : void{
        $playerName = strtolower($player instanceof Player ? $player->getName() : $player);

        foreach($this->missions as $mission){
            $mission->deleteProgress($player);
        }

        // DB에서 진행 데이터 삭제
        Quests::getInstance()->getDatabaseManager()->deleteProgress($playerName, $this->id);
    }

    public function accept(Player|string $player) : void{
        $playerName = strtolower($player instanceof Player ? $player->getName() : $player);

        foreach($this->missions as $index => $mission){
            if(!$mission->isDataExist($player)){
                $mission->setProgress($player, Mission::DEFAULT_PROGRESS);
                // DB에 저장
                $this->saveProgressToDb($playerName, $index, Mission::DEFAULT_PROGRESS);
            }
        }
    }

    public function reset() : void{
        $this->clearedCache = [];

        foreach($this->missions as $mission){
            $mission->reset();
        }
    }

    public function clearCheck(Player|string $player) : void{
        $playerName = strtolower($player instanceof Player ? $player->getName() : $player);

        if($this->isClearedCached($playerName)){
            return;
        }

        // 모든 미션이 클리어되었는지 확인
        foreach($this->missions as $mission){
            if(!$mission->isCleared($player)){
                return;
            }
        }

        $this->clear($player);
    }

    public function clear(Player|string $player) : void{
        $playerName = strtolower($player instanceof Player ? $player->getName() : $player);

        if($this->isClearedCached($playerName)){
            return;
        }

        $xuid = null;
        $playerClass = null;

        if(is_string($player)){
            $playerClass = Server::getInstance()->getPlayerExact($player);

            if($playerClass !== null){
                $xuid = $playerClass->getXuid();
            }
        }else{
            $playerClass = $player;
            $playerName = strtolower($player->getName());
            $xuid = $playerClass->getXuid();
        }

        if($playerClass !== null){
            $playerClass->sendMessage(Quests::PREFIX . "퀘스트 [ {$this->displayName} ] 를 클리어 하셨습니다!");
        }

        $items = $this->getRewardItems();

        if(count($items) > 0){
            Await::f2c(function() use($playerName, $xuid, $items){
                $xuid ??= yield from XuidCore::getInstance()->getXuidByName($playerName);

                if($xuid === null){
                    return;
                }

                if(!(yield from MailCore::getInstance()->send(
                    new MailInfo(
                        null,
                        $xuid,
                        $this->displayName . " 클리어 보상",
                        "퀘스트 클리어 축하드려요!",
                        0,
                        $items
                    )
                ))){
                    Server::getInstance()->getLogger()->error("퀘스트 보상 지급 실패: {quest:{$this->id},xuid:{$xuid}");
                }
            });
        }

        // 미션 진행 데이터 삭제
        foreach($this->missions as $mission){
            $mission->deleteProgress($player);
        }

        // 메모리 캐시 업데이트
        $this->clearedCache[$playerName] = true;
        Quests::getInstance()->getLogger()->debug("[$playerName] 퀘스트 '{$this->displayName}' (ID: {$this->id}) 클리어 캐시 업데이트 완료");

        // DB에 클리어 기록 저장
        Quests::getInstance()->getDatabaseManager()->saveCleared($playerName, $this->id);

        // DB에서 진행 데이터 삭제
        Quests::getInstance()->getDatabaseManager()->deleteProgress($playerName, $this->id);

        // 가이드 퀘스트 완료 시 타이틀 알림 업데이트 (다음 퀘스트 표시 또는 제거)
        if($this->type === self::TYPE_GUIDE && $playerClass !== null && $playerClass->isConnected()){
            Quests::getInstance()->sendQuestNotification($playerClass);
        }
    }

    public function isCleared(Player|string $player) : bool{
        $playerName = strtolower($player instanceof Player ? $player->getName() : $player);
        return $this->isClearedCached($playerName);
    }

    public function isClearedCached(string $playerName) : bool{
        return $this->clearedCache[strtolower($playerName)] ?? false;
    }

    public function setClearedCache(string $playerName, bool $cleared) : void{
        $this->clearedCache[strtolower($playerName)] = $cleared;
    }

    public function isTrying(Player|string $player) : bool{
        // 가이드 퀘스트와 일일 퀘스트는 항상 수락된 상태
        if($this->type === self::TYPE_DAILY || $this->type === self::TYPE_GUIDE){
            return true;
        }

        // 일반 퀘스트는 미션에 진행 데이터가 있으면 수락 중
        foreach($this->missions as $mission){
            if($mission->isDataExist($player)){
                return true;
            }
        }

        return false;
    }

    public function isAutoAccept() : bool{
        return $this->type === self::TYPE_DAILY || $this->type === self::TYPE_GUIDE;
    }

    public function saveProgressToDb(string $playerName, int $missionIndex, int $progress) : void{
        Quests::getInstance()->getDatabaseManager()->saveProgress($playerName, $this->id, $missionIndex, $progress);
    }
}
