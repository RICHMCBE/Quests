<?php

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\info\QuestInfoIntegration;
use naeng\quests\quest\missions\Mission;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;

class CommandMission extends Mission{

    public const NAME = "명령어 사용하기";

    public function __construct(
        private readonly string $command,
        private readonly string $displayName
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getCommand() : string{
        return $this->command;
    }

    public function getDisplayName() : string{
        return $this->displayName;
    }

    public function getInformation() : string{
        return "/{$this->command} 명령어 사용하기";
    }

    public function currentProgress(Player|string $player) : string{
        $cleared = $this->isCleared($player) ? "§a(완료)" : "§c(미완료)";
        return "/{$this->command} 명령어 사용하기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= 1;
    }

    public function handleCommandEvent(CommandEvent $event) : void{
        $sender = $event->getSender();
        if(!$sender instanceof Player){
            return;
        }

        $command = $event->getCommand();
        // 명령어 파싱 (슬래시 제거 및 첫 번째 단어만 추출)
        $command = ltrim($command, "/");
        $commandParts = explode(" ", $command);
        $baseCommand = strtolower($commandParts[0]);

        // 대상 명령어와 일치하지 않으면 무시
        if($baseCommand !== strtolower($this->command)){
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

        // 명령어 사용 완료 처리
        $this->setProgress($sender, 1);

        // 완료 메시지 전송
        if($this->quest !== null){
            $sender->sendPopup("§a§l[미션 완료] §r§f{$this->displayName}");
            $this->quest->clearCheck($sender);
        }

        // InfoPlugin 스코어보드 업데이트
        QuestInfoIntegration::updateScoreboard($sender);
    }

    public function jsonSerialize() : array{
        return [
            "name" => self::NAME,
            "command" => $this->command,
            "displayName" => $this->displayName,
            "playerData" => $this->playerData
        ];
    }

    public static function jsonDeserialize(array $data) : static{
        $mission = new static(
            $data["command"],
            $data["displayName"]
        );
        $mission->setPlayerData($data["playerData"] ?? []);
        return $mission;
    }
}
