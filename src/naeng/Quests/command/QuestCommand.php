<?php

namespace naeng\Quests\command;

use naeng\Quests\quest\Quest;
use naeng\Quests\quest\QuestFactory;
use naeng\Quests\Quests;
use NaengUtils\form\ButtonForm;
use NaengUtils\form\ModalForm;
use NaengUtils\form\NaengForm;
use NaengUtils\NaengUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class QuestCommand extends Command{

    private readonly QuestFactory $questFactory;

    public function __construct(){
        $this->questFactory = Quests::getInstance()->getQuestFactory();
        parent::__construct("퀘스트", "퀘스트 명령어 입니다", "/퀘스트");
        $this->setPermission("quests.user.command");
    }

    public function getStatusMessage(Quest $quest, Player $player) : string{
        if($quest->isCleared($player)){
            return "§a● §8클리어 한 퀘스트 입니다";
        }elseif($quest->isTrying($player)){
            return "§6● §8도전 중인 퀘스트 입니다";
        }
        return "§c● §8클릭 후 도전하세요";
    }

    public function addQuestButton(ButtonForm $form, Quest $quest, Player $player) : void{
        $form->addButton(
            name: [$quest->getName(), $this->getStatusMessage($quest, $player)],
            closure: function(Player $player) use($quest) : void{
                $form = new ModalForm();
                if($quest->isCleared($player)){
                    $player->sendMessage(Quests::PREFIX . "이미 클리어 한 퀘스트 입니다");
                }elseif($quest->isTrying($player)){
                    $form->setTitle("퀘스트: {$quest->getName()}");
                    $content = ["§6● §f§l수행 해야하는 미션", ""];
                    foreach($quest->getMissions() as $mission){
                        $content[] = " §r§f- {$mission->currentProgress($player)}";
                    }
                    $content[] = "";
                    $content[] = "§6● §f§l퀘스트 클리어 보상";
                    if($quest->getRewardIslandProgress() > 0){
                        $content[] = " §r§f- 섬 진척도 {$quest->getRewardIslandProgress()}§a§lP";
                    }
                    foreach($quest->getRewardItems() as $item){
                        $content[] = " §r§f- 아이템 | " . NaengUtils::getKoreanName($item, $item->getName()) . " {$item->getCount()}개";
                    }
                    $form->setContent($content);
                    $form->setButton1(
                        name: "퀘스트 목록으로 돌아가기..",
                        closure: function(Player $player) : void{
                            $this->execute($player, "", []);
                        }
                    );
                    $form->setButton2(
                        name: "퀘스트 포기하기..",
                        closure: function(Player $player) use($quest) : void{
                            $quest->giveUp($player);
                            $player->sendMessage(Quests::PREFIX . "퀘스트를 포기했습니다: {$quest->getName()}");
                        }
                    );
                    $player->sendForm($form);
                }else{
                    $form->setTitle("퀘스트를 수락하시겠습니까?");
                    $content = ["§6● §f§l수행 해야하는 미션"];
                    foreach($quest->getMissions() as $mission){
                        $content[] = " §r§f- {$mission->getInformation()}";
                    }
                    $content[] = "";
                    $content[] = "§6● §f§l퀘스트 클리어 보상";
                    if($quest->getRewardIslandProgress() > 0){
                        $content[] = " §r§f- 섬 진척도 {$quest->getRewardIslandProgress()}§a§lP";
                    }
                    foreach($quest->getRewardItems() as $item){
                        $content[] = " §r§f- 아이템 | " . NaengUtils::getKoreanName($item, $item->getName()) . " {$item->getCount()}개";
                    }
                    $form->setContent($content);
                    $form->setButton1(
                        name: "퀘스트 목록으로 돌아가기..",
                        closure: function(Player $player) : void{
                            $this->execute($player, "", []);
                        }
                    );
                    $form->setButton2(
                        name: "퀘스트 수락하기..",
                        closure: function(Player $player) use($quest) : void{
                            $quest->accept($player);
                            $player->sendMessage(Quests::PREFIX . "퀘스트를 수락했습니다: {$quest->getName()}");
                        }
                    );
                    $player->sendForm($form);
                }
            }
        );
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
        $form = new ModalForm();
        $form->setTitle("퀘스트");
        $form->setContent("어떤 종류의 퀘스트를 확인 하시겠습니까?");
        $form->setButton1(
            name: "일일 퀘스트",
            closure: function(Player $player) : void{
                $form = new ButtonForm();
                foreach($this->questFactory->getDailyQuests() as $quest){
                    $this->addQuestButton($form, $quest, $player);
                }
                $player->sendForm($form);
            }
        );
        $form->setButton2(
            name: "일반 퀘스트",
            closure: function(Player $player) : void{
                $form = new ButtonForm();
                foreach($this->questFactory->getNormalQuests() as $quest){
                    $this->addQuestButton($form, $quest, $player);
                }
                $player->sendForm($form);
            }
        );
        $sender->sendForm($form);
    }

}