<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;

/**
 * 잠수광산에서 아이템을 획득하면 진행되는 미션.
 *
 * DivingMine 플러그인에서 아이템 지급 시 Quests::getInstance()->handleDivingMineAcquire($player) 를 호출해야 합니다.
 */
class DivingMineAcquireMission extends Mission{

    public const NAME = "잠수광산 아이템 획득하기";

    public function __construct(
        private readonly int $count = 5
    ){}

    public function getName() : string{
        return self::NAME;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getInformation() : string{
        return "잠수광산에서 아이템 {$this->count}개 획득하기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "잠수광산 아이템 {$progress}/{$this->count}개 획득 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    /**
     * DivingMine에서 아이템 획득 시 호출
     */
    public function handleAcquire(Player $player) : void{
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

        $progress    = $this->getProgress($player) ?? 0;
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        if($this->quest !== null){
            if($newProgress >= $this->count){
                $player->sendPopup("§r丌 §f{$this->getInformation()}");
            }else{
                $player->sendPopup("§r不 §f{$this->getInformation()} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
