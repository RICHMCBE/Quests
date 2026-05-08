<?php

namespace naeng\quests\form;

use Generator;
use naeng\quests\command\QuestAdminCommand;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use naeng\quests\Quests;
use pocketmine\form\Form;
use pocketmine\player\Player;
use RoMo\HelloPlayer\HelloPlayer;
use SOFe\AwaitGenerator\Await;

class AdminForceClearForm implements Form{

    /** @var string[] 폼 전송 시점에 확정된 플레이어 이름 목록 */
    private array $playerNames = [];

    public function __construct(
        private readonly QuestAdminCommand $command,
        private readonly QuestFactory $questFactory,
        private readonly Quest $quest
    ){}

    public function jsonSerialize() : array{
        $this->playerNames = $this->fetchPlayerNames();

        $buttons = [];
        foreach($this->playerNames as $name){
            $buttons[] = ["text" => "§r§l{$name}"];
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

        if(empty($this->playerNames)){
            $player->sendForm(new AdminQuestDetailForm($this->command, $this->questFactory, $this->quest));
            return;
        }

        $targetName = $this->playerNames[$data] ?? null;
        if($targetName === null) return;

        Await::f2c(function() use($player, $targetName) : Generator{
            $clearedCount = yield from Quests::getInstance()->forceClearGuideQuests(
                $targetName,
                $this->quest->getId()
            );

            if($clearedCount <= 0){
                $player->sendMessage("§r与 {$targetName} §f의 해당 퀘스트는 이미 클리어되었습니다");
                return;
            }

            $player->sendMessage("§r丌 {$targetName} §f의 [ {$this->quest->getDisplayName()} ] 를 강제 클리어했습니다");

            // 대상이 현재 서버에 있으면 알림 전송
            $target = Quests::getInstance()->getServer()->getPlayerExact($targetName);
            if($target !== null && $target !== $player){
                $target->sendMessage("§r丌 관리자에 의해 퀘스트가 강제 클리어되었습니다");
            }
        });
    }

    /** @return string[] */
    private function fetchPlayerNames() : array{
        if(class_exists(HelloPlayer::class)){
            $names = [];
            foreach(HelloPlayer::getInstance()->getProxiedPlayers() as $proxied){
                $names[] = $proxied->getName();
            }
            return $names;
        }

        // HelloPlayer 없을 때 로컬 서버 플레이어로 폴백
        return array_map(
            fn(Player $p) => $p->getName(),
            array_values(Quests::getInstance()->getServer()->getOnlinePlayers())
        );
    }
}
