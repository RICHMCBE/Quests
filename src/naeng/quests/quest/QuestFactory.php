<?php

namespace naeng\quests\quest;

use naeng\quests\Quests;
use pocketmine\player\Player;
use Symfony\Component\Filesystem\Path;

class QuestFactory{

    /**
     * @var Quest[]
     */
    private array $quests = [];

    public function __construct(){
        if(file_exists($this->getDataDirectory())){
            foreach(yaml_parse_file($this->getDataDirectory()) as $arraySerializedQuest){
                $quest = Quest::jsonDeserialize($arraySerializedQuest);
                $this->quests[$quest->getName()] = $quest;
            }
        }
    }

    public function save() : void{
        $quests = [];
        foreach($this->quests as $quest){
            $quests[] = $quest->jsonSerialize();
        }
        yaml_emit_file($this->getDataDirectory(), $quests, YAML_UTF8_ENCODING);
    }

    public function getDataDirectory() : string{
        return Path::join(Quests::getInstance()->getDataFolder(), 'quests.yml');
    }

    public function getQuests() : array{
        return $this->quests;
    }

    public function getQuest(string $questName) : ?Quest{
        return ($this->quests[$questName] ?? null);
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

    public function getDailyQuests() : array{
        return $this->getQuestsByType(Quest::TYPE_DAILY);
    }

    public function getNormalQuests() : array{
        return $this->getQuestsByType(Quest::TYPE_NORMAL);
    }

    public function addQuest(Quest $quest) : bool{
        $name = $quest->getName();
        if(isset($this->quests[$name])){
            return false;
        }
        $this->quests[$name] = $quest;
        return true;
    }

    public function removeQuest(Quest|string $quest) : bool{
        $name = $quest instanceof Quest ? $quest->getName() : $quest;
        if(!isset($this->quests[$name])){
            return false;
        }
        unset($this->quests[$name]);
        return true;
    }

    public function getClearedQuests(Player|string $player) : array{
        $quests = [];
        foreach($this->quests as $quest){
            if($quest->isCleared($player)){
                $quests[] = $quest;
            }
        }
        return $quests;
    }

}