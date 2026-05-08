<?php

namespace naeng\quests\quest;

use pocketmine\player\Player;

class QuestFactory{

    /** @var array<string, Quest> */
    private array $quests = [];

    public function __construct(){
        $this->loadQuests();
    }

    private function loadQuests() : void{
        // 하드코딩된 퀘스트 로드
        foreach(QuestRegistry::getQuests() as $quest){
            $this->quests[$quest->getId()] = $quest;
        }
    }

    public function addQuest(Quest $quest) : void{
        $this->quests[$quest->getId()] = $quest;
    }

    /**
     * @return Quest[]
     */
    public function getQuests() : array{
        return $this->quests;
    }

    public function getQuest(string $questId) : ?Quest{
        return $this->quests[$questId] ?? null;
    }

    /**
     * @return Quest[]
     */
    public function getQuestsByType(int $type) : array{
        $quests = [];
        foreach($this->quests as $quest){
            if($quest->getType() === $type){
                $quests[] = $quest;
            }
        }

        // ID 순서대로 정렬하여 일관된 순서 보장
        usort($quests, function(Quest $a, Quest $b) : int {
            return strnatcmp($a->getId(), $b->getId());
        });

        return $quests;
    }

    /**
     * @return Quest[]
     */
    public function getDailyQuests() : array{
        return $this->getQuestsByType(Quest::TYPE_DAILY);
    }

    /**
     * @return Quest[]
     */
    public function getNormalQuests() : array{
        return $this->getQuestsByType(Quest::TYPE_NORMAL);
    }

    /**
     * @return Quest[]
     */
    public function getGuideQuests() : array{
        return $this->getQuestsByType(Quest::TYPE_GUIDE);
    }

    /**
     * @return Quest[]
     */
    public function getClearedQuests(Player|string $player) : array{
        $quests = [];
        foreach($this->quests as $quest){
            if($quest->isCleared($player)){
                $quests[] = $quest;
            }
        }
        return $quests;
    }

    public function resetDailyQuests() : void{
        foreach($this->getDailyQuests() as $quest){
            $quest->reset();
        }
    }

    /**
     * 일일 퀘스트 미션을 새로 생성 (랜덤 미션 재선택)
     * 날짜 리셋 후 호출
     */
    public function rebuildDailyQuests() : void{
        foreach(QuestRegistry::getDailyQuests() as $quest){
            $this->quests[$quest->getId()] = $quest;
        }
    }

    /**
     * 현재 진행 중인 가이드 퀘스트 반환
     */
    public function getCurrentGuideQuest(Player $player) : ?Quest{
        foreach($this->getGuideQuests() as $quest){
            if(!$quest->isCleared($player)){
                return $quest;
            }
        }
        return null;
    }

    /**
     * 현재 가이드 퀘스트의 단계 번호 반환
     */
    public function getCurrentQuestStage(Player $player) : int{
        $stage = 1;
        foreach($this->getGuideQuests() as $quest){
            if(!$quest->isCleared($player)){
                return $stage;
            }
            $stage++;
        }
        return $stage;
    }
}
