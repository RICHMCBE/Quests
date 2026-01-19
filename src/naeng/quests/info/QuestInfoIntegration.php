<?php

declare(strict_types=1);

namespace naeng\quests\info;

use naeng\quests\quest\Quest;
use naeng\quests\Quests;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\Server;
use RoMo\InfoPlugin\InfoPlugin;

class QuestInfoIntegration{

    // InfoPlugin의 lines와 충돌하지 않도록 높은 번호 사용
    private const SCORE_START = 100;
    private const MAX_LINES = 15;

    /** @var array<string, int> 플레이어별 현재 표시 중인 라인 수 */
    private static array $currentLineCount = [];

    private static bool $registered = false;

    public static function register() : void{
        if(self::$registered){
            return;
        }

        $plugin = Server::getInstance()->getPluginManager()->getPlugin("InfoPlugin");
        if(!$plugin instanceof InfoPlugin){
            Quests::getInstance()->getLogger()->warning("InfoPlugin을 찾을 수 없습니다");
            return;
        }

        self::$registered = true;
        Quests::getInstance()->getLogger()->info("InfoPlugin 스코어보드 연동 완료");
    }

    /**
     * 플레이어의 퀘스트 스코어보드 업데이트
     */
    public static function updateScoreboard(Player $player) : void{
        if(!$player->isConnected()){
            return;
        }

        $plugin = Server::getInstance()->getPluginManager()->getPlugin("InfoPlugin");
        if(!$plugin instanceof InfoPlugin){
            return;
        }

        $playerName = $player->getName();

        // 기존 퀘스트 라인 제거
        self::removeQuestLines($player);

        // 새 퀘스트 라인 추가
        $lines = self::buildQuestLines($player);
        self::$currentLineCount[$playerName] = count($lines);

        if(count($lines) === 0){
            return;
        }

        $entries = [];
        foreach($lines as $index => $line){
            $entry = new ScorePacketEntry();
            $entry->objectiveName = "InfoPlugin";
            $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
            $entry->customName = $line;
            $entry->score = self::SCORE_START + $index;
            $entry->scoreboardId = self::SCORE_START + $index;
            $entries[] = $entry;
        }

        $packet = new SetScorePacket();
        $packet->type = SetScorePacket::TYPE_CHANGE;
        $packet->entries = $entries;
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    /**
     * 기존 퀘스트 라인 제거
     */
    private static function removeQuestLines(Player $player) : void{
        $playerName = $player->getName();
        $previousCount = self::$currentLineCount[$playerName] ?? 0;

        if($previousCount === 0){
            return;
        }

        $entries = [];
        for($i = 0; $i < $previousCount; $i++){
            $entry = new ScorePacketEntry();
            $entry->objectiveName = "InfoPlugin";
            $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
            $entry->customName = "";
            $entry->score = self::SCORE_START + $i;
            $entry->scoreboardId = self::SCORE_START + $i;
            $entries[] = $entry;
        }

        $packet = new SetScorePacket();
        $packet->type = SetScorePacket::TYPE_REMOVE;
        $packet->entries = $entries;
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    /**
     * 퀘스트 라인들 생성
     */
    private static function buildQuestLines(Player $player) : array{
        $quest = self::getCurrentGuideQuest($player);

        // 모든 가이드 퀘스트 클리어 시 빈 배열 반환 (라인 숨김)
        if($quest === null){
            return [];
        }

        $lines = [];

        // 빈 줄 (구분선)
        $lines[] = "";

        // 퀘스트 제목
        $stage = self::getCurrentQuestStage($player);
        $lines[] = "§e§l{$stage}. {$quest->getDisplayName()}";

        // 미션들
        foreach($quest->getMissions() as $mission){
            $target = self::getMissionTarget($mission);

            // 가이드 타입이 아니면 건너뛰기
            if($target < 0){
                continue;
            }

            $isCleared = $mission->isCleared($player);
            $progress = $mission->getProgress($player) ?? 0;
            $info = $mission->getInformation();

            $checkMark = $isCleared ? "§a✓" : "§7○";
            $color = $isCleared ? "§a" : "§f";
            $progressColor = $isCleared ? "§a" : "§7";

            // 미션 정보
            $missionLine = "{$checkMark} {$color}{$info}";
            $lines[] = $missionLine;

            // 진행도
            $lines[] = "   {$progressColor}({$progress}/{$target})";
        }

        return $lines;
    }

    /**
     * 현재 가이드 퀘스트의 단계 번호 반환
     */
    public static function getCurrentQuestStage(Player $player) : int{
        $guideQuests = Quests::getInstance()->getQuestFactory()->getGuideQuests();
        $stage = 1;

        foreach($guideQuests as $quest){
            if(!$quest->isCleared($player)){
                return $stage;
            }
            $stage++;
        }

        return $stage;
    }

    /**
     * 총 가이드 퀘스트 개수 반환
     */
    public static function getTotalQuestCount() : int{
        return count(Quests::getInstance()->getQuestFactory()->getGuideQuests());
    }

    private static function getMissionTarget($mission) : int{
        // 가이드 타입 퀘스트의 미션만 처리
        $quest = $mission->getQuest();
        if($quest === null || $quest->getType() !== Quest::TYPE_GUIDE){
            return -1;
        }

        return method_exists($mission, 'getCount') ? $mission->getCount() : 1;
    }

    public static function getCurrentGuideQuest(Player $player) : ?Quest{
        $guideQuests = Quests::getInstance()->getQuestFactory()->getGuideQuests();

        foreach($guideQuests as $quest){
            if(!$quest->isCleared($player)){
                return $quest;
            }
        }

        return null;
    }

    /**
     * 플레이어 연결 해제 시 캐시 정리
     */
    public static function onPlayerQuit(Player $player) : void{
        unset(self::$currentLineCount[$player->getName()]);
    }
}
