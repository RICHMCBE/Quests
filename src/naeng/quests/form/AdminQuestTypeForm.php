<?php

namespace naeng\quests\form;

use naeng\quests\command\QuestAdminCommand;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use pocketmine\form\Form;
use pocketmine\player\Player;

class AdminQuestTypeForm implements Form{

    public function __construct(
        private readonly QuestAdminCommand $command,
        private readonly QuestFactory $questFactory,
        private readonly array $quests,
        private readonly string $title
    ){}

    public function jsonSerialize() : array{
        $buttons = [];
        foreach($this->quests as $quest){
            $hasReward = count($quest->getRewardItems()) > 0 ? "§a보상 설정됨" : "§c보상 없음";
            $buttons[] = ["text" => "§r§l{$quest->getDisplayName()}\n§r§8{$hasReward}"];
        }

        if(empty($buttons)){
            $buttons[] = ["text" => "§r§l돌아가기\n§r§8등록된 퀘스트가 없습니다"];
        }

        return [
            "type"    => "form",
            "title"   => $this->title,
            "content" => "",
            "buttons" => $buttons
        ];
    }

    public function handleResponse(Player $player, $data) : void{
        if($data === null) return;

        $quests = array_values($this->quests);

        if(empty($quests)){
            $player->sendForm(new AdminQuestMainForm($this->command, $this->questFactory));
            return;
        }

        $quest = $quests[$data] ?? null;
        if($quest instanceof Quest){
            $player->sendForm(new AdminQuestDetailForm($this->command, $this->questFactory, $quest));
        }
    }
}
