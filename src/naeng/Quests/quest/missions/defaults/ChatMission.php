<?php

namespace naeng\Quests\quest\missions\defaults;

use naeng\Quests\quest\missions\Mission;
use naeng\Quests\quest\Quest;
use naeng\Quests\Quests;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ChatMission extends Mission{

    public const NAME = "채팅 보내기";
    public const DEFAULT_PROGRESS = 0;

    public function __construct(protected readonly string $message, protected readonly int $count, array $playerData = [], ?Quest $quest = null){
        parent::__construct($playerData, $quest);
    }

    public function currentProgress(Player|string $player): string{
        $progress = $this->getProgress($player);
        $currentProgress = "메시지 [ {$this->message} ] 전송하기";
        if($progress !== null){
            $currentProgress .= " ({$progress}/{$this->count})";
        }
        return $currentProgress;
    }

    public function getInformation() : string{
        return "채팅 [ {$this->message} ] {$this->count}번 전송하기";
    }

    public function isCleared(Player|string $player) : bool{
        return $this->getProgress($player, self::DEFAULT_PROGRESS) >= $this->count;
    }

    public function getMessage() : string{
        return $this->message;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function handlePlayerChatEvent(PlayerChatEvent $event) : void{
        $message = $event->getMessage();
        if($message !== $this->message){
            return; // 미션과 관련 없는 메시지
        }
        $player = $event->getPlayer();
        $progress = $this->getProgress($player);
        if($progress === null){
            return; // 해당 미션과 관련 없는 플레이어
        }elseif($progress >= $this->count){
            return;
        }elseif(++$progress == $this->count){
            $this->setProgress($player, $progress);
            $player->sendMessage(Quests::PREFIX . "메시지 [ {$message} ] 전송하기 미션을 클리어 했습니다");
            $this->getQuest()?->clearCheck($player);
            return; // 미션 클리어
        }
        $this->setProgress($player, $progress);
        $player->sendMessage(Quests::PREFIX . "메시지 [ {$message} ] 전송하기 미션 진행 중.. ({$progress}/{$this->count})");
    }

    public function jsonSerialize() : array{
        return [
            "name"       => self::NAME,
            "playerData" => $this->playerData,
            "message"    => $this->message,
            "count"      => $this->count
        ];
    }

    public static function jsonDeserialize(array $jsonSerializedMission) : self{
        unset($jsonSerializedMission["name"]);
        return new self(...$jsonSerializedMission);
    }

    public function equals(Mission $mission) : bool{
        if(!parent::equals($mission)){
            return false;
        }
        if(!$mission instanceof ChatMission){
            return false;
        }
        return $this->message === $mission->getMessage() && $this->count === $mission->getCount();
    }

}