<?php

namespace naeng\quests\form;

use kim\present\koritemname\KorItemName;
use naeng\quests\command\QuestCommand;
use naeng\quests\quest\Quest;
use naeng\quests\Quests;
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

        // 미션 정보 표시
        $missions = $this->quest->getMissions();
        if(count($missions) > 0){
            $content[] = "§6● §f§l수행 해야하는 미션";
            $content[] = "";

            foreach($missions as $mission){
                $content[] = " §r§f- " . $mission->currentProgress($this->player);
            }

            $content[] = "";
        }

        $content[] = "§6● §f§l퀘스트 클리어 보상";
        $this->addRewardContent($content);

        return [
            "type" => "modal",
            "title" => "퀘스트: " . $this->quest->getDisplayName(),
            "content" => implode("\n", $content),
            "button1" => "확인",
            "button2" => "돌아가기"
        ];
    }

    private function addRewardContent(array &$content) : void {
        if($this->quest->getRewardIslandProgress() > 0) {
            $content[] = " §r§f- 섬 진척도 " . $this->quest->getRewardIslandProgress() . "§a§lP";
        }

        foreach($this->quest->getRewardItems() as $item) {
            $content[] = " §r§f- 아이템 | " . KorItemName::translate($item, true) . " " . $item->getCount() . "개";
        }

        if($this->quest->getRewardIslandProgress() === 0 && count($this->quest->getRewardItems()) === 0){
            $content[] = " §r§8- 보상 없음";
        }
    }

    public function handleResponse(Player $player, $data) : void {
        if($data === null) return;

        if(!$data) { // 돌아가기
            $this->command->execute($player, "", []);
        }
    }
}
