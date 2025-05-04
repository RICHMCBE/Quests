<?php

namespace naeng\quests\command;

use naeng\quests\form\QuestDetailForm;
use naeng\quests\form\QuestMainForm;
use naeng\quests\form\QuestTypeForm;
use naeng\quests\quest\Quest;
use naeng\quests\quest\QuestFactory;
use naeng\quests\Quests;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class QuestCommand extends Command {

    use SingletonTrait;

    private readonly QuestFactory $questFactory;

    public function __construct() {
        $this->questFactory = Quests::getInstance()->getQuestFactory();

        parent::__construct("퀘스트", "퀘스트 명령어 입니다", "/퀘스트");
        $this->setPermission("quests.user.command");
    }

    public static function getStatusMessage(Quest $quest, Player $player) : string {
        if($quest->isCleared($player)) {
            return "§a● §8클리어 한 퀘스트 입니다";
        } elseif($quest->isTrying($player)) {
            return "§6● §8도전 중인 퀘스트 입니다";
        }

        return "§c● §8클릭 후 도전하세요";
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        if(!$sender instanceof Player) {
            $sender->sendMessage(Quests::PREFIX . "게임에 접속하여 실행 해주세요");
            return;
        }

        if(!$this->testPermission($sender)) {
            $sender->sendMessage(Quests::PREFIX . "명령어를 사용할 권한이 없습니다");
            return;
        }

        $sender->sendForm(new QuestMainForm($this));
    }

    public function sendQuestTypeForm(Player $player, int $questType, string $title) : void {
        $quests = $this->questFactory->getQuestsByType($questType);
        $player->sendForm(new QuestTypeForm($this, $quests, $player, $title));
    }

    public function sendQuestDetailForm(Player $player, Quest $quest) : void {
        $player->sendForm(new QuestDetailForm($this, $quest, $player));
    }
}