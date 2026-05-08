<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;

class PlayTimeMission extends Mission{

    public const NAME = "접속시간 채우기";

    public function __construct(
        private readonly int $requiredMinutes
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getCount() : int{
        return $this->requiredMinutes;
    }

    public function getInformation() : string{
        return "서버에 {$this->requiredMinutes}분 접속하기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "접속 {$progress}/{$this->requiredMinutes}분 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->requiredMinutes;
    }

    /**
     * PlayingTime 플러그인에서 읽어온 오늘 접속 시간(초)으로 진행도 갱신
     */
    public function updateFromPlayingTime(Player $player, int $todaySeconds) : void{
        if($this->isCleared($player)){
            return;
        }

        $minutes = (int)floor($todaySeconds / 60);
        $current = $this->getProgress($player) ?? 0;

        // 진행도가 줄어드는 방향은 무시
        if($minutes <= $current){
            return;
        }

        $this->setProgress($player, $minutes);

        if($this->quest !== null){
            $this->quest->clearCheck($player);
            if($minutes >= $this->requiredMinutes){
                $player->sendPopup("§a§l[미션 완료] §r§f서버 {$this->requiredMinutes}분 접속 완료!");
            }
        }
    }
}
