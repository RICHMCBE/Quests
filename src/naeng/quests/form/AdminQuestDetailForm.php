<?php

namespace naeng\quests\form;

use Generator;
use kim\present\loader\invmenu\AwaitInvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use naeng\quests\command\QuestAdminCommand;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use naeng\quests\Quests;
use naeng\quests\utils\ItemUtils;
use pocketmine\form\Form;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class AdminQuestDetailForm implements Form{

    private const ACTION_REWARD = 0;
    private const ACTION_BACK   = 1;

    public function __construct(
        private readonly QuestAdminCommand $command,
        private readonly QuestFactory $questFactory,
        private readonly Quest $quest
    ){}

    public function jsonSerialize() : array{
        $hasReward = count($this->quest->getRewardItems()) > 0 ? "§a보상 설정됨" : "§c보상 없음";

        return [
            "type"    => "form",
            "title"   => $this->quest->getDisplayName(),
            "content" => "§8{$hasReward}",
            "buttons" => [
                ["text" => "§r§l아이템 보상 변경하기\n§r§8아이템 보상을 설정합니다"],
                ["text" => "§r§l돌아가기\n§r§8퀘스트 목록으로 돌아갑니다"],
            ]
        ];
    }

    public function handleResponse(Player $player, $data) : void{
        if($data === null) return;

        match((int)$data){
            self::ACTION_REWARD => $this->openRewardEditor($player),
            self::ACTION_BACK   => $player->sendForm(new AdminQuestMainForm($this->command, $this->questFactory)),
            default => null
        };
    }

    private function openRewardEditor(Player $player) : void{
        Await::f2c(function() use($player) : Generator{
            $menu = AwaitInvMenu::create(InvMenuTypeIds::TYPE_CHEST);
            $menu->setName("§l{$this->quest->getDisplayName()} §r§8보상 편집");

            $currentRewards = $this->quest->getRewardItems();
            foreach($currentRewards as $index => $item){
                $menu->getInventory()->setItem($index, $item);
            }

            $menu->send($player);
            yield from $menu->awaitClose();

            $items = [];
            foreach($menu->getInventory()->getContents() as $item){
                if(!$item->isNull()){
                    $items[] = $item;
                }
            }

            $this->saveRewards($player, $items);
        });
    }

    private function saveRewards(Player $player, array $items) : void{
        $db = Quests::getInstance()->getDatabaseManager();

        if(count($items) === 0){
            $db->deleteRewards($this->quest->getId());
            $this->quest->setRewardItems([]);
            $player->sendMessage("§r丌 {$this->quest->getDisplayName()} §f퀘스트의 보상이 삭제되었습니다");
            return;
        }

        $serialized = ItemUtils::serializeList($items);
        if($serialized === null){
            $player->sendMessage("§r下 아이템 저장에 실패했습니다");
            return;
        }

        $db->saveRewards($this->quest->getId(), $serialized);
        $this->quest->setRewardItems($items);

        $player->sendMessage("§r丌 {$this->quest->getDisplayName()} §f퀘스트의 보상이 저장되었습니다");
        $player->sendMessage("§r不 저장된 보상:");
        foreach($items as $item){
            $player->sendMessage("§r不 {$item->getName()} x{$item->getCount()}");
        }
    }
}
