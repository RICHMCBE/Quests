<?php

namespace naeng\quests\command;

use Generator;
use naeng\quests\form\QuestMainForm;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use naeng\quests\Quests;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
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
            $sender->sendMessage("§r与 게임에 접속하여 실행 해주세요");
            return;
        }

        if(!$this->testPermission($sender)){
            $sender->sendMessage("§r下 명령어를 사용할 권한이 없습니다");
            return;
        }

        if(($args[0] ?? "") === "가이드클리어" || ($args[0] ?? "") === "guideclear"){
            $targetName = $args[1] ?? "";
            if($targetName === ""){
                $sender->sendMessage("§r不 사용법: /퀘스트관리 가이드클리어 <플레이어> [현재|전체|guide_id]");
                return;
            }

            $target = Quests::getInstance()->getServer()->getPlayerExact($targetName);
            if(!$target instanceof Player){
                $sender->sendMessage("§r下 대상 플레이어가 온라인 상태가 아닙니다");
                return;
            }

            $selection = $args[2] ?? "current";
            $normalizedSelection = strtolower($selection);
            if($normalizedSelection !== "current" && $normalizedSelection !== "현재" && $normalizedSelection !== "all" && $normalizedSelection !== "전체"){
                $quest = $this->questFactory->getQuest($selection);
                if($quest === null || $quest->getType() !== Quest::TYPE_GUIDE){
                    $sender->sendMessage("§r下 가이드 퀘스트 ID를 찾을 수 없습니다: {$selection}");
                    return;
                }
            }

            Await::f2c(function() use($sender, $target, $selection) : Generator{
                $clearedCount = yield from Quests::getInstance()->forceClearGuideQuests($target, $selection);

                if($clearedCount <= 0){
                    $sender->sendMessage("§r与 강제 클리어할 가이드 퀘스트가 없습니다");
                    return;
                }

                $sender->sendMessage("§r丌 {$target->getName()} §f플레이어의 가이드 퀘스트 {$clearedCount}개를 강제 클리어했습니다");
                if($sender !== $target){
                    $target->sendMessage("§r丌 관리자에 의해 가이드 퀘스트가 강제 클리어되었습니다");
                }
            });
            return;
        }

        $sender->sendForm(new QuestMainForm(QuestCommand::getInstance()));
    }
}
