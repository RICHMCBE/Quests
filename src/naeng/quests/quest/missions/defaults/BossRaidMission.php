<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use cherrychip\boss\event\BossRaidVictoryEvent;
use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;
use pocketmine\Server;

class BossRaidMission extends Mission{

    public const NAME = "보스 레이드 클리어";

    /**
     * @param string|null $bossId  null이면 모든 보스 카운트
     * @param int         $count   클리어 횟수 목표
     */
    public function __construct(
        private readonly ?string $bossId,
        private readonly int $count
    ){}

    public function getName() : string{
        return self::NAME;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getBossId() : ?string{
        return $this->bossId;
    }

    public function getInformation() : string{
        $bossLabel = $this->bossId !== null ? "[{$this->bossId}] 보스" : "보스";
        return "{$bossLabel} 레이드 {$this->count}회 클리어";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared = $this->isCleared($player) ? "§a(완료)" : "";
        $bossLabel = $this->bossId ?? "보스";
        return "{$bossLabel} 레이드 {$progress}/{$this->count}회 클리어 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    public function handleBossRaidVictoryEvent(BossRaidVictoryEvent $event) : void{
        if($this->bossId !== null && $event->getBossId() !== $this->bossId){
            return;
        }

        foreach($event->getParticipantXuids() as $xuid){
            $player = $this->getPlayerByXuid($xuid);
            if($player === null){
                continue;
            }

            if(!$this->isTrying($player)){
                if($this->quest !== null && $this->quest->isAutoAccept() && !$this->quest->isCleared($player)){
                    $this->setProgress($player, self::DEFAULT_PROGRESS);
                }else{
                    continue;
                }
            }

            if($this->isCleared($player)){
                continue;
            }

            $progress = (int) ($this->getProgress($player) ?? 0);
            $newProgress = $progress + 1;
            $this->setProgress($player, $newProgress);

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
    }

    private function getPlayerByXuid(int $xuid) : ?Player{
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if((int) $player->getXuid() === $xuid){
                return $player;
            }
        }
        return null;
    }
}
