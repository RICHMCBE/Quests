<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;
use RoMo\FishPlugin\event\FishCatchEvent;

class FishCatchMission extends Mission{

    public const NAME = "물고기 낚기";

    public function __construct(
        private readonly int $count
    ){}

    public function getName() : string{
        return self::NAME;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getInformation() : string{
        return "물고기 {$this->count}마리 낚기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared = $this->isCleared($player) ? "§a(완료)" : "";
        return "물고기 {$progress}/{$this->count}마리 낚기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    public function handleFishCatchEvent(FishCatchEvent $event) : void{
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

        $progress = (int) ($this->getProgress($player) ?? 0);
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        if($this->quest !== null){
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendPopup("§r丌 §f{$this->getInformation()}");
            }else{
                $player->sendPopup("§r不 §f{$this->getInformation()} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
