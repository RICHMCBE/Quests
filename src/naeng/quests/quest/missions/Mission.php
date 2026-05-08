<?php

namespace naeng\quests\quest\missions;

use alvin0319\VotifierAPI\event\PlayerVoteEvent;
use naeng\quests\quest\Quest;
use naeng\quests\Quests;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

abstract class Mission{

    public const DEFAULT_PROGRESS = 0;

    protected ?Quest $quest = null;
    protected int $index = 0;

    /**
     * @var array<string, mixed>
     */
    protected array $playerData = [];

    abstract public function getName() : string;

    abstract public function getInformation() : string;

    abstract public function currentProgress(Player|string $player) : string;

    abstract public function isCleared(Player|string $player) : bool;

    public function setQuest(Quest $quest) : void{
        $this->quest = $quest;
    }

    public function getQuest() : ?Quest{
        return $this->quest;
    }

    public function setIndex(int $index) : void{
        $this->index = $index;
    }

    public function getIndex() : int{
        return $this->index;
    }

    public function getPlayerData() : array{
        return $this->playerData;
    }

    public function setPlayerData(array $playerData) : void{
        $this->playerData = $playerData;
    }

    public function getProgress(Player|string $player) : mixed{
        $name = strtolower($player instanceof Player ? $player->getName() : $player);
        return $this->playerData[$name] ?? null;
    }

    public function setProgress(Player|string $player, mixed $progress, bool $saveToDb = true) : void{
        $name = strtolower($player instanceof Player ? $player->getName() : $player);
        $this->playerData[$name] = $progress;

        // DB에 비동기 저장 (메모리 상태가 우선, DB는 영속성 보장용)
        if($saveToDb && $this->quest !== null){
            $this->quest->saveProgressToDb($name, $this->index, (int)$progress);
        }

        // 가이드 퀘스트인 경우
        if($this->quest !== null && $this->quest->getType() === Quest::TYPE_GUIDE){
            $playerInstance = $player instanceof Player ? $player : Server::getInstance()->getPlayerExact($name);
            if($playerInstance !== null && $playerInstance->isConnected()){
                // 먼저 클리어 체크 (완료 시 캐시 업데이트 + clear()가 자체적으로 알림 예약)
                $this->quest->clearCheck($playerInstance);
                // 퀘스트가 클리어되지 않은 경우에만 진행도 표시 업데이트 예약
                // (클리어된 경우 clear()가 이미 알림을 예약했으므로 중복 전송 방지)
                if(!$this->quest->isCleared($playerInstance)){
                    Quests::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
                        function() use($playerInstance) : void{
                            if($playerInstance->isConnected()){
                                Quests::getInstance()->sendQuestNotification($playerInstance);
                            }
                        }
                    ), 60);
                }
            }
        }
    }

    public function deleteProgress(Player|string $player) : void{
        $name = strtolower($player instanceof Player ? $player->getName() : $player);
        unset($this->playerData[$name]);
    }

    public function isDataExist(Player|string $player) : bool{
        $name = strtolower($player instanceof Player ? $player->getName() : $player);
        return isset($this->playerData[$name]);
    }

    public function isTrying(Player|string $player) : bool{
        return $this->isDataExist($player);
    }

    public function reset() : void{
        $this->playerData = [];
    }

    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
    }

    public function handleBlockPlaceEvent(BlockPlaceEvent $event) : void{
    }

    public function handleCommandEvent(CommandEvent $event) : void{
    }

    public function handleChatEvent(PlayerChatEvent $event) : void{
    }

    public function handleVoteEvent(PlayerVoteEvent $event) : void{
    }
}
