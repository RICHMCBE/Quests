<?php

namespace naeng\Quests\quest\missions\defaults;

use naeng\Quests\quest\missions\Mission;
use naeng\Quests\quest\Quest;
use naeng\Quests\Quests;
use NaengUtils\NaengUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class BringItemMission extends Mission{

    public const NAME = "아이템 가져오기";
    public const DEFAULT_PROGRESS = false;

    public function __construct(protected readonly string $nameTag, protected readonly Item $item, array $playerData = [], ?Quest $quest = null){
        parent::__construct($playerData, $quest);
    }

    public function currentProgress(Player|string $player): string{
        $progress = $this->getProgress($player);
        $currentProgress = explode("\n", $this->nameTag)[0] . "에게 아이템 [ " . NaengUtils::getKoreanName($this->item, $this->item->getName()) . " ] {$this->item->getCount()}개 가져오기";
        if($progress !== null){
            $currentProgress .= " (" . ($progress ? "클리어" : "도전 중") . ")";
        }
        return $currentProgress;
    }

    public function getInformation() : string{
        return explode("\n", $this->nameTag)[0] . "에게 아이템 [ " . NaengUtils::getKoreanName($this->item, $this->item->getName()) . " ] {$this->item->getCount()}개 가져오기";
    }

    public function isCleared(Player|string $player) : bool{
        return $this->getProgress($player, self::DEFAULT_PROGRESS);
    }

    public function getItem() : Item{
        return clone $this->item;
    }

    public function getNameTag() : string{
        return $this->nameTag;
    }

    public function handleEntityDamageByEntityEvent(EntityDamageByEntityEvent $event) : void{
        if($event->getEntity()->getNameTag() !== $this->nameTag){
            return; // 네임태그가 다름
        }
        $damager = $event->getDamager();
        if(!$damager instanceof Player){
            return;
        }
        $progress = $this->getProgress($damager);
        if($progress === null){
            return; // 해당 미션과 관련 없는 플레이어
        }elseif($progress === true){
            return; // 이미 클리어한 미션
        }
        $inventory = $damager->getInventory();
        $item = $this->getItem();
        if(!$inventory->contains($item)){
            return; // 아이템을 소지하고 있지 않음
        }
        $inventory->removeItem($item);
        $this->setProgress($damager, true);
        $damager->sendMessage(Quests::PREFIX . "아이템 [ " . NaengUtils::getKoreanName($item, $item->getName()) . " ] 가져오기 미션을 클리어 했습니다");
        $this->getQuest()?->clearCheck($damager);
    }

    public function jsonSerialize() : array{
        return [
            "name"       => self::NAME,
            "playerData" => $this->playerData,
            "nameTag"    => $this->nameTag,
            "item"       => NaengUtils::itemStringSerialize($this->item)
        ];
    }

    public static function jsonDeserialize(array $jsonSerializedMission) : self{
        unset($jsonSerializedMission["name"]);
        $jsonSerializedMission["item"] = NaengUtils::itemStringDeserialize($jsonSerializedMission["item"]);
        return new self(...$jsonSerializedMission);
    }

    public function equals(Mission $mission) : bool{
        if(!parent::equals($mission)){
            return false;
        }
        if(!$mission instanceof BringItemMission){
            return false;
        }
        return $this->nameTag === $mission->getNameTag() && $this->item->equalsExact($mission->getItem());
    }

}