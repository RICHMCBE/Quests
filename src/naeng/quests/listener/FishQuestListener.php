<?php

declare(strict_types=1);

namespace naeng\quests\listener;

use naeng\quests\quest\missions\defaults\FishCatchMission;
use naeng\quests\Quests;
use pocketmine\event\Listener;
use RoMo\FishPlugin\event\FishCatchEvent;

class FishQuestListener implements Listener{

    public function handleFishCatchEvent(FishCatchEvent $event) : void{
        foreach(Quests::getInstance()->getQuestFactory()->getQuests() as $quest){
            foreach($quest->getMissions() as $mission){
                if($mission instanceof FishCatchMission){
                    $mission->handleFishCatchEvent($event);
                }
            }
        }
    }
}
