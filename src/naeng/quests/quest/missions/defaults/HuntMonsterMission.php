<?php

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use naeng\quests\quest\Quest;
use naeng\quests\Quests;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\player\Player;
use RoMo\MonsterPlugin\entity\MonsterEntity;

class HuntMonsterMission extends Mission{

    public const NAME = "몬스터 사냥하기";
    public const DEFAULT_PROGRESS = 0;

    public function __construct(protected readonly int $count, array $playerData = [], ?Quest $quest = null){
        parent::__construct($playerData, $quest);
    }

    public function currentProgress(Player|string $player): string{
        $progress = $this->getProgress($player);

        return $this->getInformation() . ($progress === null ? "" : " ({$progress}/{$this->count})");
    }

    public function getInformation() : string{
        return "몬스터 {$this->count}마리 사냥하기";
    }

    public function isCleared(Player|string $player) : bool{
        return $this->getProgress($player, self::DEFAULT_PROGRESS) >= $this->count;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function handleEntityDeathEvent(EntityDeathEvent $event) : void{
        $monster = $event->getEntity();
        if(!($monster instanceof MonsterEntity)){
            return;
        }

        $player = $monster->getLastAttack();
        if($player === null){
            return;
        }

        $progress = $this->getProgress($player);

        if($progress === null){
            return; // 해당 미션과 관련 없는 플레이어
        }elseif($progress >= $this->count){
            return;
        }elseif(++$progress == $this->count){
            $this->setProgress($player, $progress);
            $player->sendMessage(Quests::PREFIX . $this->getInformation() . " 미션을 클리어 했습니다");
            $this->getQuest()?->clearCheck($player);
            return;
        } // 미션 클리어

        $this->setProgress($player, $progress);
        $player->sendTip(Quests::PREFIX . self::NAME . " 미션 진행 중..\n ({$progress}/{$this->count})");
    }

    public function jsonSerialize() : array{
        return [
            "name"       => self::NAME,
            "playerData" => $this->playerData,
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

        if(!$mission instanceof HuntMonsterMission){
            return false;
        }

        return $this->count === $mission->getCount();
    }

}
