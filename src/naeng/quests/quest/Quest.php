<?php

namespace naeng\quests\quest;

use kim\present\utils\itemserialize\SnbtItemSerializer;
use naeng\MailCore\data\MailInfo;
use naeng\MailCore\MailCore;
use naeng\quests\quest\missions\defaults\BreakBlockMission;
use naeng\quests\quest\missions\defaults\BringItemMission;
use naeng\quests\quest\missions\defaults\ChatMission;
use naeng\quests\quest\missions\defaults\CommandMission;
use naeng\quests\quest\missions\defaults\HuntMonsterMission;
use naeng\quests\quest\missions\Mission;
use naeng\quests\Quests;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use RoMo\XuidCore\XuidCore;
use SOFe\AwaitGenerator\Await;

class Quest{

    public const TYPE_DAILY  = 0;
    public const TYPE_NORMAL = 1;

    /**
     * @param Mission[] $missions
     * @param Item[] $rewardItems
     * @param string[] $clearedPlayers
     */
    public function __construct(
        private readonly string $name,
        private          int    $type,
        private          array  $missions = [],
        private          array  $rewardItems = [],
        private          int    $rewardIslandProgress = 0,
        private          array  $clearedPlayers = []
    ){
        $this->refreshMissionData();
    }

    public function refreshMissionData() : void{
        $players = [];

        foreach($this->missions as $mission){
            foreach($mission->getPlayerData() as $name => $data){
                $players[$name] = true;
            }
        }

        foreach($this->missions as $mission){
            foreach($players as $name => $_){
                if(!$mission->isDataExist($name)){
                    $mission->setProgress($name, $mission::DEFAULT_PROGRESS);
                }
            }
        }
    }

    public function getName() : string{
        return $this->name;
    }

    public function getType() : int{
        return $this->type;
    }

    public function setType(int $type) : void{
        $this->type = $type;
    }

    public function getMissions() : array{
        return $this->missions;
    }

    public function setMissions(array $missions) : void{
        $this->missions = $missions;
        $this->refreshMissionData();
    }

    public function addMission(Mission $mission) : void{
        $mission->setQuest($this);

        $this->missions[] = $mission;
        $this->refreshMissionData();
    }

    public function getRewardItems() : array{
        return $this->rewardItems;
    }

    public function setRewardItems(array $items) : void{
        $this->rewardItems = $items;
    }

    public function getRewardIslandProgress() : int{
        return $this->rewardIslandProgress;
    }

    public function setRewardIslandProgress(int $islandProgress) : void{
        $this->rewardIslandProgress = $islandProgress;
    }

    public function removeMission(Mission $mission) : void{
        foreach($this->missions as $key => $value){
            if($value->equals($mission)){
                unset($this->missions[$key]);
            }
        }

        $this->refreshMissionData();
    }

    public function giveUp(Player|string $player) : void{
        foreach($this->missions as $mission){
            $mission->deleteProgress($player);
        }
    }

    public function accept(Player|string $player) : void{
        foreach($this->missions as $mission){
            $mission->setProgress($player, $mission::DEFAULT_PROGRESS);
        }
    }

    public function reset() : void{
        $this->clearedPlayers = [];

        foreach($this->missions as $mission){
            $mission->reset();
        }
    }

    public function clearCheck(Player|string $player) : void{
        if(!$this->isCleared($player)){
            return;
        }

        $xuid = null;

        if(is_string($player)){
            $playerClass = Server::getInstance()->getPlayerExact($player);
            $playerName = $player;

            if($playerClass !== null){
                $xuid = $playerClass->getXuid();
            }
        }else{
            $playerClass = $player;
            $playerName = $player->getName();
            $xuid = $playerClass->getXuid();
        }

        if($playerClass !== null){
            $playerClass->sendMessage(Quests::PREFIX . "퀘스트 [ {$this->name} ] 를 클리어 하셨습니다!");
        }

        $items = $this->getRewardItems();

        if(count($items) > 0){
            Await::f2c(function() use($playerName, $xuid, $items){
                $xuid ??= yield from XuidCore::getInstance()->getXuidByName($playerName);

                if($xuid === null){
                    return;
                }

                if(!(yield from MailCore::getInstance()->send(
                    new MailInfo(
                        null,
                        $xuid,
                        $this->name . " 클리어 보상",
                        "퀘스트 클리어 축하드려요!",
                        0,
                        $items
                    )
                ))){
                    Server::getInstance()->getLogger()->error("퀘스트 보상 지급 실패: {quest:{$this->name},xuid:{$xuid}");
                }
            });
        }

        foreach($this->missions as $mission){
            $mission->deleteProgress($player);
        }

        $this->clearedPlayers[] = strtolower($playerName);
        // TODO : 섬 진척도 보상 지급
    }

    public function isCleared(Player|string $player) : bool{
        if(in_array(strtolower($player instanceof Player ? $player->getName() : $player), $this->clearedPlayers)){
            return true;
        }

        foreach($this->missions as $mission){
            if(!$mission->isCleared($player)){
                return false;
            }
        }

        return true;
    }

    public function isTrying(Player $player) : bool{
        foreach($this->missions as $mission){
            if($mission->isTrying($player)){
                return true;
            }
        }
        return false;
    }

    public function jsonSerialize() : array{
        $missions = [];
        foreach($this->missions as $mission){
            $missions[] = $mission->jsonSerialize();
        }

        return [
            "name"                 => $this->name,
            "type"                 => $this->type,
            "missions"             => $missions,
            "rewardItems"          => SnbtItemSerializer::serializeList($this->rewardItems),
            "rewardIslandProgress" => $this->rewardIslandProgress,
            "clearedPlayers"       => $this->clearedPlayers
        ];
    }

    public static function jsonDeserialize(array $jsonSerializedData) : self{
        $missions = [];
        foreach($jsonSerializedData["missions"] as $jsonSerializedMission){
            switch($jsonSerializedMission["name"]){
                case BringItemMission::NAME:
                    $missions[] = BringItemMission::jsonDeserialize($jsonSerializedMission);
                    break;
                case BreakBlockMission::NAME:
                    $missions[] = BreakBlockMission::jsonDeserialize($jsonSerializedMission);
                    break;
                case ChatMission::NAME:
                    $missions[] = ChatMission::jsonDeserialize($jsonSerializedMission);
                    break;
                case CommandMission::NAME:
                    $missions[] = CommandMission::jsonDeserialize($jsonSerializedMission);
                    break;
                case HuntMonsterMission::NAME:
                    $missions[] = HuntMonsterMission::jsonDeserialize($jsonSerializedMission);
                    break;
            }
        }

        $jsonSerializedData["missions"] = $missions;
        $jsonSerializedData["rewardItems"] = SnbtItemSerializer::deserializeList($jsonSerializedData["rewardItems"]);

        $quest = new self(...$jsonSerializedData);
        foreach($quest->getMissions() as $mission){
            $mission->setQuest($quest);
        }

        return $quest;
    }

}