<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\block\Block;
use pocketmine\block\Carrot;
use pocketmine\block\MelonStem;
use pocketmine\block\Potato;
use pocketmine\block\PumpkinStem;
use pocketmine\block\Wheat;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\player\Player;

class PlantCropMission extends Mission{

    public const NAME = "작물 심기";

    public const PLANT_WHEAT   = "wheat";
    public const PLANT_CARROT  = "carrot";
    public const PLANT_POTATO  = "potato";
    public const PLANT_MELON   = "melon";
    public const PLANT_PUMPKIN = "pumpkin";

    public static array $plantNames = [
        self::PLANT_WHEAT   => "밀 씨앗",
        self::PLANT_CARROT  => "당근",
        self::PLANT_POTATO  => "감자",
        self::PLANT_MELON   => "수박 씨앗",
        self::PLANT_PUMPKIN => "호박 씨앗",
    ];

    public function __construct(
        private readonly string $plantType,
        private readonly int    $count
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getPlantType() : string{
        return $this->plantType;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getPlantDisplayName() : string{
        return self::$plantNames[$this->plantType] ?? $this->plantType;
    }

    public function getInformation() : string{
        return "{$this->getPlantDisplayName()} {$this->count}개 심기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "{$this->getPlantDisplayName()} {$progress}/{$this->count}개 심기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    private function isMatchingPlant(Block $block) : bool{
        return match($this->plantType){
            self::PLANT_WHEAT   => $block instanceof Wheat,
            self::PLANT_CARROT  => $block instanceof Carrot,
            self::PLANT_POTATO  => $block instanceof Potato,
            self::PLANT_MELON   => $block instanceof MelonStem,
            self::PLANT_PUMPKIN => $block instanceof PumpkinStem,
            default             => false,
        };
    }

    public function handleBlockPlaceEvent(BlockPlaceEvent $event) : void{
        $player = $event->getPlayer();

        if(!$this->isTrying($player)){
            if($this->quest !== null && $this->quest->isAutoAccept() && !$this->quest->isCleared($player)){
                $this->setProgress($player, self::DEFAULT_PROGRESS);
            }else{
                return;
            }
        }

        if($this->isCleared($player)){
            return;
        }

        $found = false;
        foreach($event->getTransaction()->getBlocks() as [, , , $block]){
            if($this->isMatchingPlant($block)){
                $found = true;
                break;
            }
        }
        if(!$found){
            return;
        }

        $progress    = $this->getProgress($player) ?? 0;
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        if($this->quest !== null){
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendPopup("§a§l[미션 완료] §r§f{$this->getPlantDisplayName()} {$this->count}개 심기 완료!");
            }else{
                $player->sendPopup("§6§l[퀘스트] §r§f{$questName} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
