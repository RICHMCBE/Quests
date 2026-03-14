<?php

declare(strict_types=1);

namespace naeng\quests\event;

use naeng\quests\quest\Quest;
use pocketmine\event\Event;
use pocketmine\player\Player;

class QuestClearEvent extends Event{

    public function __construct(
        private Player $player,
        private Quest  $quest
    ){
    }

    public function getPlayer() : Player{
        return $this->player;
    }

    public function getQuest() : Quest{
        return $this->quest;
    }
}
