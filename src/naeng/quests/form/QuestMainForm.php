<?php

namespace naeng\quests\form;

use naeng\quests\command\QuestCommand;
use naeng\quests\quest\Quest;
use pocketmine\form\Form;
use pocketmine\player\Player;

class QuestMainForm implements Form {

    public function __construct(private readonly QuestCommand $command) { }

    public function jsonSerialize() : array {
        return [
            "type" => "form",
            "title" => "퀘스트",
            "content" => "어떤 종류의 퀘스트를 확인하시겠습니까?",
            "buttons" => [
                ["text" => "§r§l일일 퀘스트\n§r§8일일 발생 퀘스트를 확인합니다"],
                ["text" => "§r§l일반 퀘스트\n§r§8일반 퀘스트를 확인합니다"],
                ["text" => "§r§l길라잡이 퀘스트\n§r§8초보자를 위한 튜토리얼 퀘스트를 확인합니다"]
            ]
        ];
    }

    public function handleResponse(Player $player, $data) : void {
        if($data === null) return;

        match($data) {
            0 => $this->command->sendQuestTypeForm($player, Quest::TYPE_DAILY, "일일 퀘스트"),
            1 => $this->command->sendQuestTypeForm($player, Quest::TYPE_NORMAL, "일반 퀘스트"),
            2 => $this->command->sendQuestTypeForm($player, Quest::TYPE_GUIDE, "길라잡이 퀘스트"),
            default => null
        };
    }
}