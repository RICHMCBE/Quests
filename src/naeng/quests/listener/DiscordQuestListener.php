<?php

declare(strict_types=1);

namespace naeng\quests\listener;

use naeng\DiscordCore\event\RegisterDiscordIdEvent;
use naeng\quests\Quests;
use pocketmine\event\Listener;
use RoMo\XuidCore\XuidCore;

class DiscordQuestListener implements Listener{

    /**
     * @priority MONITOR
     */
    public function handleRegisterDiscordIdEvent(RegisterDiscordIdEvent $event) : void{
        $player = XuidCore::getInstance()->getPlayer($event->getXuid());
        if($player === null){
            return;
        }

        Quests::getInstance()->handleDiscordAuth($player);
    }
}
