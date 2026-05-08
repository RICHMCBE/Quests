<?php

namespace naeng\quests\quest\missions\defaults;

use alvin0319\VotifierAPI\event\PlayerVoteEvent;
use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;
use pocketmine\Server;

class VoteMission extends Mission{

    public const NAME = "마인리스트 추천하기";

    public function __construct(private readonly int $count = 1){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getInformation() : string{
        return $this->count > 1
            ? "마인리스트에서 서버 추천하기 ({$this->count}회)"
            : "마인리스트에서 서버 추천하기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "§c(미완료)";
        return "마인리스트 추천 {$progress}/{$this->count}회 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    public function handleVoteEvent(PlayerVoteEvent $event) : void{
        $userName = $event->getUsername();
        $userName = str_replace("_", " ", $userName);
        $playerName = strtolower($userName);

        // 이미 완료했으면 스킵
        if($this->isCleared($playerName)){
            return;
        }

        // 일일 퀘스트는 자동 수락이므로, 진행 데이터가 없으면 생성
        if(!$this->isTrying($playerName)){
            if($this->quest !== null && $this->quest->isAutoAccept() && !$this->quest->isCleared($playerName)){
                $this->setProgress($playerName, self::DEFAULT_PROGRESS);
            }else{
                return;
            }
        }

        // 추천 횟수 증가
        $current = $this->getProgress($playerName) ?? 0;
        $newProgress = $current + 1;
        $this->setProgress($playerName, $newProgress);

        // 온라인 플레이어에게 메시지 전송
        $player = Server::getInstance()->getPlayerExact($userName);
        if($player !== null && $this->quest !== null){
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendTip("§r丌 §f{$this->getInformation()}");
            }else{
                $player->sendTip("§r不 §f{$this->getInformation()} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }elseif($this->quest !== null){
            // 오프라인 상태에서 추천한 경우도 완료 체크
            $this->quest->clearCheck($playerName);
        }
    }

    public function jsonSerialize() : array{
        return [
            "name" => self::NAME,
            "playerData" => $this->playerData
        ];
    }

    public static function jsonDeserialize(array $data) : static{
        $mission = new static();
        $mission->setPlayerData($data["playerData"] ?? []);
        return $mission;
    }
}
