<?php

namespace naeng\quests\form;

use naeng\quests\command\QuestAdminCommand;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use pocketmine\form\Form;
use pocketmine\player\Player;

class AdminQuestMainForm implements Form{

    public function __construct(
        private readonly QuestAdminCommand $command,
        private readonly QuestFactory $questFactory
    ){}

    public function jsonSerialize() : array{
        return [
            "type"    => "form",
            "title"   => "§l퀘스트 관리",
            "content" => "어떤 종류의 퀘스트를 관리하시겠습니까?",
            "buttons" => [
                ["text" => "§r§l일일 퀘스트\n§r§8일일 퀘스트를 관리합니다"],
                ["text" => "§r§l일반 퀘스트\n§r§8일반 퀘스트를 관리합니다"],
                ["text" => "§r§l길라잡이 퀘스트\n§r§8길라잡이 퀘스트를 관리합니다"],
            ]
        ];
    }

    public function handleResponse(Player $player, $data) : void{
        if($data === null) return;

        $typeMap = [
            0 => [Quest::TYPE_DAILY,  "일일 퀘스트"],
            1 => [Quest::TYPE_NORMAL, "일반 퀘스트"],
            2 => [Quest::TYPE_GUIDE,  "길라잡이 퀘스트"],
        ];

        [$type, $title] = $typeMap[(int)$data] ?? [null, null];
        if($type === null) return;

        $quests = $this->questFactory->getQuestsByType($type);
        $player->sendForm(new AdminQuestTypeForm($this->command, $this->questFactory, $quests, $title));
    }
}
