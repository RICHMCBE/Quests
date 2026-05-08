<?php

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;

class ChatMission extends Mission{

    public const NAME = "채팅하기";

    public function __construct(
        private readonly string $message,
        private readonly string $displayName
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getMessage() : string{
        return $this->message;
    }

    public function getDisplayName() : string{
        return $this->displayName;
    }

    public function getInformation() : string{
        return "{$this->message} 채팅으로 입력하기";
    }

    public function currentProgress(Player|string $player) : string{
        $cleared = $this->isCleared($player) ? "§a(완료)" : "§c(미완료)";
        return "{$this->message} 채팅으로 입력하기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= 1;
    }

    public function handleChatEvent(PlayerChatEvent $event) : void{
        $sender = $event->getPlayer();
        $message = $event->getMessage();

        // 대상 메시지와 일치하지 않으면 무시
        if($message !== $this->message){
            return;
        }

        // 이미 완료했으면 스킵
        if($this->isCleared($sender)){
            return;
        }

        // 가이드 퀘스트는 자동 수락이므로, 진행 데이터가 없으면 생성
        if(!$this->isTrying($sender)){
            if($this->quest !== null && $this->quest->isAutoAccept() && !$this->quest->isCleared($sender)){
                $this->setProgress($sender, self::DEFAULT_PROGRESS);
            }else{
                return;
            }
        }

        // 채팅 완료 처리
        $this->setProgress($sender, 1);

        // 완료 메시지 전송
        if($this->quest !== null){
            $sender->sendPopup("§r丌 {$this->displayName}");
            $this->quest->clearCheck($sender);
        }
        // 업데이트는 setProgress()와 clear()에서 자동으로 호출됨
    }

    public function jsonSerialize() : array{
        return [
            "name" => self::NAME,
            "message" => $this->message,
            "displayName" => $this->displayName,
            "playerData" => $this->playerData
        ];
    }

    public static function jsonDeserialize(array $data) : static{
        $mission = new static(
            $data["message"],
            $data["displayName"]
        );
        $mission->setPlayerData($data["playerData"] ?? []);
        return $mission;
    }
}
