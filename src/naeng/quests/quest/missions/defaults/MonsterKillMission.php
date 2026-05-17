<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;

class MonsterKillMission extends Mission {

    public const NAME = "몬스터 사냥하기";

    public const TYPE_COW     = "cow";
    public const TYPE_PIG     = "pig";
    public const TYPE_CHICKEN = "chicken";
    public const TYPE_SHEEP   = "sheep";
    public const TYPE_CRAB    = "crab";
    public const TYPE_ANY     = "any";

    private static array $typeNames = [
        self::TYPE_COW     => "소",
        self::TYPE_PIG     => "돼지",
        self::TYPE_CHICKEN => "닭",
        self::TYPE_SHEEP   => "양",
        self::TYPE_CRAB    => "꽃게",
        self::TYPE_ANY     => "몬스터",
    ];

    public function __construct(
        private readonly string $monsterType,
        private readonly int    $count
    ) {}

    public function getName() : string {
        return self::NAME;
    }

    public function getMonsterType() : string {
        return $this->monsterType;
    }

    public function getCount() : int {
        return $this->count;
    }

    public function getDisplayName() : string {
        return self::$typeNames[$this->monsterType] ?? $this->monsterType;
    }

    public function getInformation() : string {
        return "{$this->getDisplayName()} {$this->count}마리 처치";
    }

    public function currentProgress(Player|string $player) : string {
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "{$this->getDisplayName()} {$progress}/{$this->count}마리 처치 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool {
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    public function handleMonsterKill(Player $player, string $killedType) : void {
        // TYPE_ANY는 모든 몬스터 처치 카운트, 아니면 타입이 일치해야 함
        if ($this->monsterType !== self::TYPE_ANY && $this->monsterType !== $killedType) {
            return;
        }

        if (!$this->isTrying($player)) {
            if ($this->quest !== null && $this->quest->isAutoAccept() && !$this->quest->isCleared($player)) {
                $this->setProgress($player, self::DEFAULT_PROGRESS);
            } else {
                return;
            }
        }

        if ($this->isCleared($player)) {
            return;
        }

        $progress    = $this->getProgress($player) ?? 0;
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        if ($this->quest !== null) {
            if ($newProgress >= $this->count) {
                $player->sendPopup("§r丌 §f{$this->getInformation()}");
            } else {
                $player->sendPopup("§r不 §f{$this->getInformation()} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
