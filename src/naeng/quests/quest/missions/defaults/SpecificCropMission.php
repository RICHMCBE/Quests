<?php

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\block\Beetroot;
use pocketmine\block\Block;
use pocketmine\block\Carrot;
use pocketmine\block\Crops;
use pocketmine\block\Melon;
use pocketmine\block\NetherWartPlant;
use pocketmine\block\Potato;
use pocketmine\block\Pumpkin;
use pocketmine\block\Sugarcane;
use pocketmine\block\Wheat;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;

class SpecificCropMission extends Mission{

    public const NAME = "특정 농작물 수확하기";

    public const CROP_WHEAT = "wheat";
    public const CROP_CARROT = "carrot";
    public const CROP_POTATO = "potato";
    public const CROP_BEETROOT = "beetroot";
    public const CROP_NETHER_WART = "nether_wart";
    public const CROP_MELON = "melon";
    public const CROP_PUMPKIN = "pumpkin";
    public const CROP_SUGARCANE = "sugarcane";

    public static array $cropNames = [
        self::CROP_WHEAT => "밀",
        self::CROP_CARROT => "당근",
        self::CROP_POTATO => "감자",
        self::CROP_BEETROOT => "비트",
        self::CROP_NETHER_WART => "네더와트",
        self::CROP_MELON => "수박",
        self::CROP_PUMPKIN => "호박",
        self::CROP_SUGARCANE => "사탕수수"
    ];

    public function __construct(
        private readonly string $cropType,
        private readonly int $count
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getCropDisplayName() : string{
        return self::$cropNames[$this->cropType] ?? $this->cropType;
    }

    public function getInformation() : string{
        return "{$this->getCropDisplayName()} {$this->count}개 수확하기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared = $this->isCleared($player) ? "§a(완료)" : "";
        return "{$this->getCropDisplayName()} {$progress}/{$this->count}개 수확하기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    private function isMatchingCrop(Block $block) : bool{
        return match($this->cropType){
            self::CROP_WHEAT => $block instanceof Wheat && $block->getAge() >= $block->getMaxAge(),
            self::CROP_CARROT => $block instanceof Carrot && $block->getAge() >= $block->getMaxAge(),
            self::CROP_POTATO => $block instanceof Potato && $block->getAge() >= $block->getMaxAge(),
            self::CROP_BEETROOT => $block instanceof Beetroot && $block->getAge() >= $block->getMaxAge(),
            self::CROP_NETHER_WART => $block instanceof NetherWartPlant && $block->getAge() >= $block->getMaxAge(),
            self::CROP_MELON => $block instanceof Melon,
            self::CROP_PUMPKIN => $block instanceof Pumpkin,
            self::CROP_SUGARCANE => $block instanceof Sugarcane,
            default => false
        };
    }

    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $block = $event->getBlock();

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

        if(!$this->isMatchingCrop($block)){
            return;
        }

        $progress = $this->getProgress($player) ?? 0;
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        // Tip 메시지로 진행 상태 표시
        if($this->quest !== null){
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendPopup("§a§l[퀘스트 완료] §r§f{$questName}");
            }else{
                $player->sendPopup("§6§l[퀘스트] §r§f{$questName} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }

    public function jsonSerialize() : array{
        return [
            "name" => self::NAME,
            "cropType" => $this->cropType,
            "count" => $this->count,
            "playerData" => $this->playerData
        ];
    }

    public static function jsonDeserialize(array $data) : static{
        $mission = new static(
            $data["cropType"],
            $data["count"]
        );
        $mission->setPlayerData($data["playerData"] ?? []);
        return $mission;
    }

    public function getCropType() : string{
        return $this->cropType;
    }

    public function getCount() : int{
        return $this->count;
    }
}
