<?php

namespace naeng\quests\form;

use naeng\quests\command\QuestCommand;
use naeng\quests\Quests;
use pocketmine\form\Form;
use pocketmine\player\Player;

class QuestTypeForm implements Form {

    public function __construct(
        private readonly QuestCommand $command,
        private array $quests,
        private readonly Player $player,
        private readonly string $title
    ) { }

    public function jsonSerialize() : array {
        $buttons = [];
        foreach($this->quests as $quest) {
            $buttons[] = [
                "text" => "§r§l" . $quest->getName() . "\n§r§8" .
                    $this->command->getStatusMessage($quest, $this->player)
            ];
        }

        return [
            "type" => "form",
            "title" => $this->title,
            "content" => count($buttons) > 0 ? "퀘스트를 선택해주세요:" : "현재 이용 가능한 퀘스트가 없습니다.",
            "buttons" => count($buttons) > 0 ? $buttons : [
                ["text" => "§r§l돌아가기\n§r§8메인 메뉴로 돌아갑니다"]
            ]
        ];
    }

    public function handleResponse(Player $player, $data) : void {
        if($data === null) return;

        if(count($this->quests) === 0) {
            $this->command->execute($player, "", []);
            return;
        }

        if(isset($this->quests[$data])) {
            $quest = $this->quests[$data];

            // 클리어된 퀘스트는 단순히 메시지만 보여줌
            if($quest->isCleared($player)) {
                $player->sendMessage(Quests::PREFIX . "이미 클리어한 퀘스트입니다.");
                return;
            }

            $this->command->sendQuestDetailForm($player, $quest);
        }
    }
}