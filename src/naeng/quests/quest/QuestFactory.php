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
}
