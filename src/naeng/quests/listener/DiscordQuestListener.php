<?php

declare(strict_types=1);

namespace naeng\quests\listener;

use naeng\DiscordCore\event\RegisterDiscordIdEvent;
use naeng\quests\Quests;
use pocketmine\event\Listener;
use pocketmine\Server;

class DiscordQuestListener implements Listener{

    /**
     * @priority MONITOR
     */
    public function handleRegisterDiscordIdEvent(RegisterDiscordIdEvent $event) : void{
        $player = Server::getInstance()->getPlayerByXuid((string)$event->getXuid());
        if($player === null || !$player->isOnline()){
            return;
        }

        Quests::getInstance()->handleDiscordAuth($player);
    }
}
