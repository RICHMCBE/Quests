<?php

namespace naeng\quests\command;

use Generator;
use kim\present\loader\invmenu\AwaitInvMenu;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use naeng\quests\Quests;
use naeng\quests\utils\ItemUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\form\Form;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class QuestAdminCommand extends Command{

    private readonly QuestFactory $questFactory;

    public function __construct(){
        $this->questFactory = Quests::getInstance()->getQuestFactory();

        parent::__construct("퀘스트관리", "퀘스트 관리 명령어", "/퀘스트관리");
        $this->setPermission("quests.staff.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
        if(!$sender instanceof Player){
            $sender->sendMessage(Quests::PREFIX . "게임에 접속하여 실행 해주세요");
            return;
        }

        if(!$this->testPermission($sender)){
            $sender->sendMessage(Quests::PREFIX . "명령어를 사용할 권한이 없습니다");
            return;
        }

        $this->sendQuestListForm($sender);
    }

    private function sendQuestListForm(Player $player) : void{
        $quests = $this->questFactory->getQuests();

        if(count($quests) === 0){
            $player->sendMessage(Quests::PREFIX . "등록된 퀘스트가 없습니다");
            return;
        }

        $typeNames = [
            Quest::TYPE_DAILY => "§6[일일]",
            Quest::TYPE_NORMAL => "§a[일반]",
            Quest::TYPE_GUIDE => "§b[가이드]"
        ];

        $buttons = [];
        $questList = [];

        foreach($quests as $quest){
            $typeName = $typeNames[$quest->getType()] ?? "§7[기타]";
            $hasReward = count($quest->getRewardItems()) > 0 ? "§a보상 설정됨" : "§c보상 없음";
            $buttons[] = [
                "text" => "{$typeName} §f{$quest->getDisplayName()}\n§8{$hasReward}"
            ];
            $questList[] = $quest;
        }

        $player->sendForm(new class($buttons, $questList, $this) implements Form{
            public function __construct(
                private array $buttons,
                private array $questList,
                private QuestAdminCommand $command
            ){}

            public function jsonSerialize() : array{
                return [
                    "type" => "form",
                    "title" => "§l퀘스트 관리",
                    "content" => "보상을 설정할 퀘스트를 선택하세요.\n메뉴에 아이템을 넣고 닫으면 저장됩니다.",
                    "buttons" => $this->buttons
                ];
            }

            public function handleResponse(Player $player, $data) : void{
                if($data === null){
                    return;
                }

                $quest = $this->questList[$data] ?? null;
                if($quest !== null){
                    $this->command->openRewardEditor($player, $quest);
                }
            }
        });
    }

    public function openRewardEditor(Player $player, Quest $quest) : void{
        Await::f2c(function() use($player, $quest) : Generator{
            $menu = AwaitInvMenu::create(InvMenuTypeIds::TYPE_CHEST);
            $menu->setName("§l{$quest->getDisplayName()} §r§8보상 편집");

            // 기존 보상 아이템 로드
            $currentRewards = $quest->getRewardItems();
            foreach($currentRewards as $index => $item){
                $menu->getInventory()->setItem($index, $item);
            }

            $menu->send($player);

            // 메뉴가 닫힐 때까지 대기
            yield from $menu->awaitClose();

            // 인벤토리에서 아이템 수집
            $items = [];
            foreach($menu->getInventory()->getContents() as $item){
                if(!$item->isNull()){
                    $items[] = $item;
                }
            }

            // 보상 저장
            $this->saveRewards($player, $quest, $items);
        });
    }

    private function saveRewards(Player $player, Quest $quest, array $items) : void{
        if(count($items) === 0){
            // 보상이 없으면 삭제
            Quests::getInstance()->getDatabaseManager()->deleteRewards($quest->getId());
            $quest->setRewardItems([]);
            $player->sendMessage(Quests::PREFIX . "§e{$quest->getDisplayName()} §f퀘스트의 보상이 삭제되었습니다");
            return;
        }

        // 아이템 직렬화 (여러 아이템 지원)
        $serialized = ItemUtils::serializeList($items);
        if($serialized === null){
            $player->sendMessage(Quests::PREFIX . "아이템 저장에 실패했습니다");
            return;
        }

        // DB에 저장
        Quests::getInstance()->getDatabaseManager()->saveRewards($quest->getId(), $serialized);

        // 메모리에도 반영
        $quest->setRewardItems($items);

        $player->sendMessage(Quests::PREFIX . "§a{$quest->getDisplayName()} §f퀘스트의 보상이 저장되었습니다");
        $player->sendMessage(Quests::PREFIX . "저장된 보상:");
        foreach($items as $item){
            $player->sendMessage("§7- §f{$item->getName()} x{$item->getCount()}");
        }
    }
}
