<?php

namespace naeng\quests\command;

use cosmicpe\npcdialogue\dialogue\texture\DefaultNpcDialogueTexture;
use cosmicpe\npcdialogue\NpcDialogueBuilder;
use cosmicpe\npcdialogue\NpcDialogueManager;
use kim\present\koritemname\KorItemName;
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
        // 일반 퀘스트인 경우 NpcDialogue 사용
        if($quest->getType() === Quest::TYPE_NORMAL) {
            $this->sendNpcDialogue($player, $quest, 0);
        } else {
            // 다른 퀘스트는 기존 Form 사용
            $player->sendForm(new QuestDetailForm($this, $quest, $player));
        }
    }

    /**
     * NpcDialogue를 사용하여 퀘스트 정보 표시 (페이지네이션 지원)
     */
    private function sendNpcDialogue(Player $player, Quest $quest, int $page) : void {
        $missions = $quest->getMissions();
        $itemsPerPage = 3; // 페이지당 표시할 미션 개수
        $totalPages = (int)ceil(count($missions) / $itemsPerPage);

        if($totalPages === 0) {
            $totalPages = 1;
        }

        // 페이지 범위 체크
        if($page < 0) {
            $page = 0;
        } elseif($page >= $totalPages) {
            $page = $totalPages - 1;
        }

        // 현재 페이지의 미션 목록 생성
        $startIndex = $page * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, count($missions));

        $dialogueText = "§6=== 퀘스트 정보 ===§r\n\n";

        // 미션 정보
        if(count($missions) > 0) {
            $dialogueText .= "§e[수행 해야하는 미션]§r\n";
            for($i = $startIndex; $i < $endIndex; $i++) {
                $mission = $missions[$i];
                $dialogueText .= "§f" . ($i + 1) . ". " . $mission->currentProgress($player) . "§r\n";
            }
            $dialogueText .= "\n";
        }

        // 보상 정보
        $dialogueText .= "§e[퀘스트 클리어 보상]§r\n";

        if($quest->getRewardIslandProgress() > 0) {
            $dialogueText .= "§f- 섬 진척도 " . $quest->getRewardIslandProgress() . "§a§lP§r\n";
        }

        foreach($quest->getRewardItems() as $item) {
            $dialogueText .= "§f- 아이템 | " . KorItemName::translate($item, true) . " " . $item->getCount() . "개§r\n";
        }

        if($quest->getRewardIslandProgress() === 0 && count($quest->getRewardItems()) === 0) {
            $dialogueText .= "§8- 보상 없음§r\n";
        }

        // 페이지 정보
        if($totalPages > 1) {
            $dialogueText .= "\n§7페이지: " . ($page + 1) . " / " . $totalPages . "§r";
        }

        // NpcDialogue 생성
        $dialogue = NpcDialogueBuilder::create()
            ->setName($quest->getDisplayName())
            ->setText($dialogueText)
            ->setDefaultNpcTexture(DefaultNpcDialogueTexture::TEXTURE_NPC_10);

        // 버튼 구성
        $hasNext = $page < $totalPages - 1;
        $hasPrev = $page > 0;

        // 이전 버튼 (이전 페이지가 있을 경우에만)
        if($hasPrev) {
            $dialogue->addSimpleButton("§l◀ 이전§r", function(Player $p) use($quest, $page) : void {
                $this->sendNpcDialogue($p, $quest, $page - 1);
            });
        }

        if($hasNext) {
            // 다음 페이지가 있는 경우: 다음 버튼
            $dialogue->addSimpleButton("§l다음 ▶§r", function(Player $p) use($quest, $page) : void {
                $this->sendNpcDialogue($p, $quest, $page + 1);
            });
        } else {
            // 마지막 페이지: 닫기 버튼
            $dialogue->addSimpleButton("§l✖ 닫기§r", function(Player $p) : void {
                // 대화창 닫힘
            });
        }

        // NpcDialogue 전송
        NpcDialogueManager::send($player, $dialogue->build());
    }
}