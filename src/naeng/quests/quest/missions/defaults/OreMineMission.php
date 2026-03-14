<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\block\Block;
use pocketmine\block\Cobblestone;
use pocketmine\block\CoalOre;
use pocketmine\block\DiamondOre;
use pocketmine\block\EmeraldOre;
use pocketmine\block\GoldOre;
use pocketmine\block\IronOre;
use pocketmine\block\Stone;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;

class OreMineMission extends Mission{

    public const NAME = "광석 채굴하기";

    public const ORE_STONE   = "stone";
    public const ORE_COAL    = "coal";
    public const ORE_IRON    = "iron";
    public const ORE_GOLD    = "gold";
    public const ORE_DIAMOND = "diamond";
    public const ORE_EMERALD = "emerald";

    public static array $oreNames = [
        self::ORE_STONE   => "조약돌",
        self::ORE_COAL    => "석탄",
        self::ORE_IRON    => "철광석",
        self::ORE_GOLD    => "금광석",
        self::ORE_DIAMOND => "다이아몬드",
        self::ORE_EMERALD => "에메랄드",
    ];

    public function __construct(
        private readonly string $oreType,
        private readonly int    $count
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getOreType() : string{
        return $this->oreType;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getOreDisplayName() : string{
        return self::$oreNames[$this->oreType] ?? $this->oreType;
    }

    public function getInformation() : string{
        return "{$this->getOreDisplayName()} {$this->count}개 캐기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "{$this->getOreDisplayName()} {$progress}/{$this->count}개 캐기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    private function isMatchingOre(Block $block) : bool{
        return match($this->oreType){
            self::ORE_STONE   => $block instanceof Stone || $block instanceof Cobblestone,
            self::ORE_COAL    => $block instanceof CoalOre,
            self::ORE_IRON    => $block instanceof IronOre,
            self::ORE_GOLD    => $block instanceof GoldOre,
            self::ORE_DIAMOND => $block instanceof DiamondOre,
            self::ORE_EMERALD => $block instanceof EmeraldOre,
            default           => false,
        };
    }

    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $block  = $event->getBlock();

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

        if(!$this->isMatchingOre($block)){
            return;
        }

        $progress    = $this->getProgress($player) ?? 0;
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        if($this->quest !== null){
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendPopup("§a§l[미션 완료] §r§f{$this->getOreDisplayName()} {$this->count}개 채굴 완료!");
            }else{
                $player->sendPopup("§6§l[퀘스트] §r§f{$questName} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
