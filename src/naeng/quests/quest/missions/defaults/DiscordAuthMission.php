<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;

/**
 * 디스코드 인증을 완료하면 진행되는 미션.
 *
 * DiscordCore 플러그인에서 인증 완료 시 Quests::getInstance()->handleDiscordAuth($player) 를 호출해야 합니다.
 */
class DiscordAuthMission extends Mission{

    public const NAME = "디스코드 인증하기";

    public function getName() : string{
        return self::NAME;
    }

    public function getInformation() : string{
        return "디스코드 인증 완료하기";
    }

    public function currentProgress(Player|string $player) : string{
        $cleared = $this->isCleared($player) ? "§a(완료)" : "§c(미완료)";
        return "디스코드 인증 완료하기 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= 1;
    }

    /**
     * DiscordCore에서 인증 완료 시 호출
     */
    public function handleAuth(Player $player) : void{
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

        $this->setProgress($player, 1);

        if($this->quest !== null){
            $player->sendPopup("§r丌 §f{$this->getInformation()}");
            $this->quest->clearCheck($player);
        }
    }

    public function jsonSerialize() : array{
        return [
            "name"       => self::NAME,
            "playerData" => $this->playerData
        ];
    }

    public static function jsonDeserialize(array $data) : static{
        $mission = new static();
        $mission->setPlayerData($data["playerData"] ?? []);
        return $mission;
    }
}
