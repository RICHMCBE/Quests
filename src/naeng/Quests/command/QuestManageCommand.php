<?php

namespace naeng\Quests\command;

use jojoe77777\FormAPI\CustomForm;
use muqsit\invmenu\InvMenu;
use naeng\Quests\quest\missions\defaults\BreakBlockMission;
use naeng\Quests\quest\missions\defaults\BringItemMission;
use naeng\Quests\quest\missions\defaults\ChatMission;
use naeng\Quests\quest\missions\defaults\CommandMission;
use naeng\Quests\quest\Quest;
use naeng\Quests\quest\QuestFactory;
use naeng\Quests\Quests;
use NaengUtils\form\ButtonForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

class QuestManageCommand extends Command{

    private readonly QuestFactory $questFactory;

    public function __construct(){
        $this->questFactory = Quests::getInstance()->getQuestFactory();
        parent::__construct("퀘스트관리", "퀘스트 관리 명령어 입니다", "/퀘스트관리");
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
        $form = new ButtonForm();
        $form->setTitle("퀘스트 관리");
        $form->addButton(
            name: ["퀘스트 생성하기", "새로운 퀘스트를 생성합니다"],
            closure: function(Player $player) : void{
                $form = new CustomForm(function(Player $player, $data) : void{
                    if($data === null){
                        return;
                    }
                    $name = $data[0];
                    $type = $data[1];
                    var_dump($type);
                    $quest = new Quest($name, $type);
                    if(!$this->questFactory->addQuest($quest)){
                        $player->sendMessage(Quests::PREFIX  . "같은 이름의 퀘스트가 이미 존재 합니다: {$name}");
                        return;
                    }
                    $player->sendMessage(Quests::PREFIX . "퀘스트를 생성 했습니다: {$name}");
                });
                $form->setTitle("퀘스트 생성하기");
                $form->addInput("\n퀘스트의 이름을 무엇으로 설정하시겠습니까?");
                $form->addDropdown("퀘스트의 타입을 선택 해주세요", ["일일 퀘스트", "일반 퀘스트"]);
                $form->addLabel("\n* 보상 추가 및 미션 추가는 '퀘스트 수정하기' 옵션에서 가능 합니다");
                $player->sendForm($form);
            }
        );
        $form->addButton(
            name: ["퀘스트 수정하기", "퀘스트 정보를 수정합니다"],
            closure: function(Player $player) : void{
                $form = new ButtonForm();
                $form->setTitle("퀘스트 수정하기");
                foreach($this->questFactory->getQuests() as $quest){
                    $form->addButton(
                        name: [$quest->getName(), ($quest->getType() === Quest::TYPE_DAILY ? "일일 퀘스트" : "일반 퀘스트")],
                        closure: function(Player $player) use($quest) : void{
                            $form = new ButtonForm();
                            $form->setTitle($quest->getName());
                            $form->addButton(
                                name: ["퀘스트 삭제하기", "퀘스트를 삭제합니다"],
                                closure: function(Player $player) use($quest) : void{
                                    $player->sendMessage(Quests::PREFIX . ($this->questFactory->removeQuest($quest) ? "퀘스트를 삭제 했습니다" : "퀘스트를 삭제할 수 없습니다"));
                                }
                            );
                            $form->addButton(
                                name: ["아이템 보상 변경하기", "아이템 보상을 변경합니다"],
                                closure: function(Player $player) use($quest) : void{
                                    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                                    $menu->getInventory()->setContents($quest->getRewardItems());
                                    $menu->setInventoryCloseListener(function(Player $player, Inventory $inventory) use($quest){
                                        $quest->setRewardItems($inventory->getContents());
                                        $player->sendMessage(Quests::PREFIX . "보상 아이템을 변경했습니다: {$quest->getName()}");
                                    });
                                    $menu->send($player, "보상 아이템을 넣어주세요!");
                                }
                            );
                            $form->addButton(
                                name: ["섬 진척도 보상 변경하기", "섬 진척도 보상을 변경합니다"],
                                closure: function(Player $player) use($quest) : void{
                                    $form = new CustomForm(function(Player $player, $data) use($quest) : void{
                                        if($data === null){
                                            return;
                                        }
                                        $islandProgress = intval($data[0] ?? 0);
                                        $quest->setRewardIslandProgress($islandProgress);
                                        $player->sendMessage(Quests::PREFIX . "섬 진척도 보상을 변경 했습니다: {$islandProgress}");
                                    });
                                    $form->setTitle("섬 진척도 보상 변경하기");
                                    $form->addInput("\n값을 입력해주세요", default: $quest->getRewardIslandProgress());
                                    $player->sendForm($form);
                                }
                            );
                            $form->addButton(
                                name: ["미션 추가하기", "새로운 미션을 추가합니다"],
                                closure: function(Player $player) use($quest) : void{
                                    $form = new ButtonForm();
                                    $form->addButton(
                                        name: ["블럭 부수기", "블럭을 부수는 미션 입니다"],
                                        closure: function(Player $player) use($quest) : void{
                                            $form = new CustomForm(function(Player $player, $data) use($quest){
                                                if($data === null){
                                                    return;
                                                }
                                                $count = intval($data[0] ?? 1);
                                                Quests::$blockBreakEventQueue[$player->getName()] = function(BlockBreakEvent $event) use($count, $quest) : void{
                                                    $player = $event->getPlayer();
                                                    $block = $event->getBlock();
                                                    $quest->addMission(new BreakBlockMission($block->getName(), $block->getStateId(), $count));
                                                    $player->sendMessage(Quests::PREFIX . "블럭 부수기 미션을 추가 했습니다");
                                                };
                                                $player->sendMessage(Quests::PREFIX . "부숴야 할 블럭을 부숴 주세요");
                                            });
                                            $form->setTitle("블럭 부수기 미션 추가");
                                            $form->addInput("블럭을 몇 번 캐야 클리어 할 수 있나요?");
                                            $player->sendForm($form);
                                        }
                                    );
                                    $form->addButton(
                                        name: ["아이템 가져오기", "엔티티에게 아이템을 가져오는 미션 입니다"],
                                        closure: function(Player $player) use($quest) : void{
                                            $form = new CustomForm(function(Player $player, $data) use($quest){
                                                if($data === null){
                                                    return;
                                                }
                                                $count = intval($data[0] ?? 1);
                                                $item = (clone $player->getInventory()->getItemInHand())->setCount($count);
                                                Quests::$entityDamageByEntityEventQueue[$player->getName()] = function(EntityDamageByEntityEvent $event) use($count, $item, $quest) : void{
                                                    $player = $event->getDamager();
                                                    if(!$player instanceof Player){
                                                        return;
                                                    }
                                                    $quest->addMission(new BringItemMission($event->getEntity()->getNameTag(), $item));
                                                    $player->sendMessage(Quests::PREFIX . "아이템 가져오기 미션을 추가 했습니다");
                                                };
                                                $player->sendMessage(Quests::PREFIX . "아이템을 가져다 줘야 할 엔티티를 찾아 터치 해주세요");
                                            });
                                            $form->setTitle("아이템 가져오기 미션 추가");
                                            $form->addInput("아이템 몇 개를 가져와야 하나요? (손에 들고 있는 아이템)");
                                            $player->sendForm($form);
                                        }
                                    );
                                    $form->addButton(
                                        name: ["채팅 전송하기", "채팅을 전송하는 미션 입니다"],
                                        closure: function(Player $player) use($quest) : void{
                                            $form = new CustomForm(function(Player $player, $data) use($quest){
                                                if($data === null){
                                                    return;
                                                }
                                                $message = $data[0] ?? '';
                                                $count = intval($data[1] ?? 1);
                                                $quest->addMission(new ChatMission($message, $count));
                                                $player->sendMessage(Quests::PREFIX . "채팅 전송하기 미션을 추가 했습니다");
                                            });
                                            $form->setTitle("채팅 전송하기 미션 추가");
                                            $form->addInput("어떤 내용을 입력 해야 하나요?");
                                            $form->addInput("몇 번 입력 해야하나요?");
                                            $player->sendForm($form);
                                        }
                                    );
                                    $form->addButton(
                                        name: ["명령어 입력하기", "명령어를 입력하는 미션 입니다"],
                                        closure: function(Player $player) use($quest) : void{
                                            $form = new CustomForm(function(Player $player, $data) use($quest){
                                                if($data === null){
                                                    return;
                                                }
                                                $command = $data[0] ?? '';
                                                $count = intval($data[1] ?? 1);
                                                $quest->addMission(new CommandMission($command, $count));
                                                $player->sendMessage(Quests::PREFIX . "명령어 입력하기 미션을 추가 했습니다");
                                            });
                                            $form->setTitle("명령어 입력하기 미션 추가");
                                            $form->addInput("어떤 내용을 입력 해야 하나요?");
                                            $form->addInput("몇 번 입력 해야하나요?");
                                            $player->sendForm($form);
                                        }
                                    );
                                    $player->sendForm($form);
                                }
                            );
                            $form->addButton(
                                name: ["미션 삭제하기", "미션을 삭제합니다"],
                                closure: function(Player $player) use($quest) : void{
                                    $form = new ButtonForm();
                                    $form->setTitle("미션 삭제하기");
                                    foreach($quest->getMissions() as $mission){
                                        $form->addButton(
                                            name: [$mission->getName(), $mission->getInformation()],
                                            closure: function(Player $player) use($quest, $mission) : void{
                                                $quest->removeMission($mission);
                                                $player->sendMessage(Quests::PREFIX . "미션을 삭제 했습니다");
                                            }
                                        );
                                    }
                                    $player->sendForm($form);
                                }
                            );
                            $player->sendForm($form);
                        }
                    );
                }
                $player->sendForm($form);
            }
        );
        $sender->sendForm($form);
    }

}