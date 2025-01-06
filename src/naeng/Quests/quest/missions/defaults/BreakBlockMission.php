<?php

namespace naeng\Quests\quest\missions\defaults;

use naeng\Quests\quest\missions\Mission;
use naeng\Quests\quest\Quest;
use naeng\Quests\Quests;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class BreakBlockMission extends Mission{

    public const NAME = "블럭 부수기";
    public const DEFAULT_PROGRESS = 0;

    public function __construct(protected readonly string $blockName, protected readonly int $stateId, protected readonly int $count, array $playerData = [], ?Quest $quest = null){
        parent::__construct($playerData, $quest);
    }

    public function currentProgress(Player|string $player): string{
        $progress = $this->getProgress($player);
        $currentProgress = "블럭 [ {$this->blockName} ] 부수기";
        if($progress !== null){
            $currentProgress .= " ({$progress}/{$this->count})";
        }
        return $currentProgress;
    }

    public function getInformation() : string{
        return "블럭 [ {$this->blockName} ] {$this->count}번 부수기";
    }

    public function isCleared(Player|string $player) : bool{
        return $this->getProgress($player, self::DEFAULT_PROGRESS) >= $this->count;
    }

    public function getBlockName() : string{
        return $this->blockName;
    }

    public function getStateId() : int{
        return $this->stateId;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function checkBlock(Block $block) : bool{
        if($block->getName() !== $this->blockName){
            return false; // 블럭 이름이 다름
        }
        if($block->getStateId() !== $this->stateId){
            return false; // 블럭의 상태가 다름
        }
        return true;
    }

    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
        $block = $event->getBlock();
        if(!$this->checkBlock($block)){
            return; // 미션과 관련 없는 블럭
        }
        $player = $event->getPlayer();
        $progress = $this->getProgress($player);
        if($progress === null){
            return; // 해당 미션과 관련 없는 플레이어
        }elseif(++$progress >= $this->count){
            $this->setProgress($player, $this->count);
            $player->sendMessage(Quests::PREFIX . "블럭 [ {$block->getName()} ] 부수기 미션을 클리어 했습니다");
            $this->getQuest()?->clearCheck($player);
            return; // 미션 클리어
        }
        $this->setProgress($player, $progress);
        $player->sendTip(Quests::PREFIX . "블럭 [ {$block->getName()} ] 부수기 미션 진행 중..\n ({$progress}/{$this->count})");
    }

    public function jsonSerialize() : array{
        return [
            "name"       => self::NAME,
            "playerData" => $this->playerData,
            "blockName"  => $this->blockName,
            "stateId"    => $this->stateId,
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
        if(!$mission instanceof BreakBlockMission){
            return false;
        }
        return $this->blockName === $mission->getBlockName() && $this->stateId === $mission->getStateId() && $this->count === $mission->getCount();
    }

}