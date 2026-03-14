<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;

/**
 * 도구 강화를 완료하면 진행되는 미션.
 *
 * ToolCore 플러그인에서 강화가 완료되면 Quests::getInstance()->handleToolUpgrade($player) 를 호출해야 합니다.
 */
class ToolUpgradeMission extends Mission{

    public const NAME = "도구 강화하기";

    public function __construct(
        private readonly int $count = 1
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getInformation() : string{
        return "도구 강화 {$this->count}회 완료하기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "도구 강화 {$progress}/{$this->count}회 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    /**
     * ToolCore에서 강화 완료 시 호출
     */
    public function handleUpgrade(Player $player) : void{
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
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendPopup("§a§l[미션 완료] §r§f도구 강화 {$this->count}회 완료!");
            }else{
                $player->sendPopup("§6§l[퀘스트] §r§f{$questName} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
