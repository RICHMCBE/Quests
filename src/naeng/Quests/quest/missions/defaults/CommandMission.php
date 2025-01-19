<?php

namespace naeng\Quests\quest\missions\defaults;

use naeng\Quests\quest\missions\Mission;
use naeng\Quests\quest\Quest;
use naeng\Quests\Quests;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class CommandMission extends Mission{

    public const NAME = "명령어 실행하기";
    public const DEFAULT_PROGRESS = 0;

    public function __construct(protected readonly string $command, protected readonly int $count, array $playerData = [], ?Quest $quest = null){
        parent::__construct($playerData, $quest);
    }

    public function currentProgress(Player|string $player): string{
        $progress = $this->getProgress($player);
        $currentProgress = "명령어 [ {$this->command} ] 입력하기";
        if($progress !== null){
            $currentProgress .= " ({$progress}/{$this->count})";
        }
        return $currentProgress;
    }

    public function getInformation() : string{
        return "명령어 [ {$this->command} ] {$this->count}번 입력하기";
    }

    public function isCleared(Player|string $player) : bool{
        return $this->getProgress($player, self::DEFAULT_PROGRESS) >= $this->count;
    }

    public function getCommand() : string{
        return $this->command;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function handleCommandEvent(CommandEvent $event) : void{
        $command = $event->getCommand();
        if($command !== $this->command){
            return; // 미션과 관련 없는 명령어
        }
        $player = $event->getSender();
        if(!($player instanceof Player)){
            return; // ConsoleCommandSender
        }
        $progress = $this->getProgress($player);
        if($progress === null){
            return; // 해당 미션과 관련 없는 플레이어
        }elseif($progress >= $this->count){
            return;
        }elseif(++$progress == $this->count){
            $this->setProgress($player, $progress);
            $player->sendMessage(Quests::PREFIX . "명령어 [ {$command} ] 입력하기 미션을 클리어 했습니다");
            $this->getQuest()?->clearCheck($player);
            return; // 미션 클리어
        }
        $this->setProgress($player, $progress);
        $player->sendTip(Quests::PREFIX . "명령어 [ {$command} ] 입력하기 미션 진행 중..\n ({$progress}/{$this->count})");
    }

    public function jsonSerialize() : array{
        return [
            "name"       => self::NAME,
            "playerData" => $this->playerData,
            "command"    => $this->command,
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
        if(!$mission instanceof CommandMission){
            return false;
        }
        return $this->command === $mission->getCommand() && $this->count === $mission->getCount();
    }

}
