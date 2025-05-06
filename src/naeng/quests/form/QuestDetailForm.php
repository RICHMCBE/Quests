<?php

namespace naeng\quests\form;

use naeng\quests\command\QuestCommand;
use naeng\quests\quest\Quest;
use naeng\quests\Quests;
use NaengUtils\NaengUtils;
use pocketmine\form\Form;
use pocketmine\player\Player;

class QuestDetailForm implements Form {

    public function __construct(
        private readonly QuestCommand $command,
        private readonly Quest $quest,
        private readonly Player $player
    ) { }

    public function jsonSerialize() : array {
        $content = [];

        if($this->quest->isTrying($this->player)) {
            $content[] = "§6● §f§l수행 해야하는 미션";
            $content[] = "";

            foreach($this->quest->getMissions() as $mission) {
                $content[] = " §r§f- " . $mission->currentProgress($this->player);
            }

            $content[] = "";
            $content[] = "§6● §f§l퀘스트 클리어 보상";

            $this->addRewardContent($content);

            return [
                "type" => "modal",
                "title" => "퀘스트: " . $this->quest->getName(),
                "content" => implode("\n", $content),
                "button1" => "퀘스트 포기하기",
                "button2" => "퀘스트 목록으로 돌아가기"
            ];
        } else {
            $content[] = "§6● §f§l수행 해야하는 미션";

            foreach($this->quest->getMissions() as $mission) {
                $content[] = " §r§f- " . $mission->getInformation();
            }

            $content[] = "";
            $content[] = "§6● §f§l퀘스트 클리어 보상";

            $this->addRewardContent($content);

            return [
                "type" => "modal",
                "title" => "퀘스트를 수락하시겠습니까?",
                "content" => implode("\n", $content),
                "button1" => "퀘스트 수락하기",
                "button2" => "돌아가기"
            ];
        }
    }

    private function addRewardContent(array &$content) : void {
        if($this->quest->getRewardIslandProgress() > 0) {
            $content[] = " §r§f- 섬 진척도 " . $this->quest->getRewardIslandProgress() . "§a§lP";
        }

        foreach($this->quest->getRewardItems() as $item) {
            $content[] = " §r§f- 아이템 | " . NaengUtils::getKoreanName($item, $item->getName()) . " " . $item->getCount() . "개";
        }
    }

    public function handleResponse(Player $player, $data) : void {
        if($data === null) return;

        if($this->quest->isTrying($player)) {
            if($data) {
                $this->quest->giveUp($player);
                $player->sendMessage(Quests::PREFIX . "퀘스트를 포기했습니다: " . $this->quest->getName());
            } else { // false = button1 (퀘스트 목록으로 돌아가기)
                $this->command->execute($player, "", []);
            }
        } else {
            if($data) { // true = button2 (퀘스트 수락하기)
                $this->quest->accept($player);
                $player->sendMessage(Quests::PREFIX . "퀘스트를 수락했습니다: " . $this->quest->getName());
            } else { // false = button1 (퀘스트 목록으로 돌아가기)
                $this->command->execute($player, "", []);
            }
        }
    }
}