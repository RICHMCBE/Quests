<?php

declare(strict_types=1);

namespace naeng\quests\listener;

use cherrychip\boss\event\BossRaidVictoryEvent;
use naeng\quests\quest\missions\defaults\BossRaidMission;
use naeng\quests\Quests;
use pocketmine\event\Listener;

class BossQuestListener implements Listener{

    public function handleBossRaidVictoryEvent(BossRaidVictoryEvent $event) : void{
        foreach(Quests::getInstance()->getQuestFactory()->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof BossRaidMission){
                    $mission->handleBossRaidVictoryEvent($event);
                }
            }
        }
    }
}
