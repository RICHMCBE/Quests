<?php

namespace naeng\quests\form;

use Generator;
use naeng\quests\command\QuestAdminCommand;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use naeng\quests\Quests;
use pocketmine\form\Form;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class AdminForceClearForm implements Form{

    /** @var Player[] */
    private array $players;

    public function __construct(
        private readonly QuestAdminCommand $command,
        private readonly QuestFactory $questFactory,
        private readonly Quest $quest
    ){
        $this->players = array_values(Quests::getInstance()->getServer()->getOnlinePlayers());
    }

    public function jsonSerialize() : array{
        $buttons = [];
        foreach($this->players as $player){
            $buttons[] = ["text" => "§r§l{$player->getName()}"];
        }

        if(empty($buttons)){
            $buttons[] = ["text" => "§r§l돌아가기\n§r§8온라인 플레이어가 없습니다"];
        }

        return [
            "type"    => "form",
            "title"   => "강제 클리어: {$this->quest->getDisplayName()}",
            "content" => "클리어할 플레이어를 선택하세요:",
            "buttons" => $buttons
        ];
    }

    public function handleResponse(Player $player, $data) : void{
        if($data === null) return;

        if(empty($this->players)){
            $player->sendForm(new AdminQuestDetailForm($this->command, $this->questFactory, $this->quest));
            return;
        }

        $target = $this->players[$data] ?? null;
        if(!$target instanceof Player || !$target->isConnected()){
            $player->sendMessage("§r下 대상 플레이어가 오프라인 상태입니다");
            return;
        }

        Await::f2c(function() use($player, $target) : Generator{
            $clearedCount = yield from Quests::getInstance()->forceClearGuideQuests($target, $this->quest->getId());

            if($clearedCount <= 0){
                $player->sendMessage("§r与 {$target->getName()} §f의 해당 퀘스트는 이미 클리어되었습니다");
                return;
            }

            $player->sendMessage("§r丌 {$target->getName()} §f의 [ {$this->quest->getDisplayName()} ] 를 강제 클리어했습니다");
            if($player !== $target){
                $target->sendMessage("§r丌 관리자에 의해 퀘스트가 강제 클리어되었습니다");
            }
        });
    }
}
