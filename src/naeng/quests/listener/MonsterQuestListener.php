<?php

declare(strict_types=1);

namespace naeng\quests\listener;

use cherrychip\monster\entity\Chicken;
use cherrychip\monster\entity\Cow;
use cherrychip\monster\entity\Crab;
use cherrychip\monster\entity\Pig;
use cherrychip\monster\entity\Sheep;
use cherrychip\monster\event\MonsterDeathEvent;
use naeng\quests\quest\missions\defaults\MonsterKillMission;
use naeng\quests\Quests;
use pocketmine\event\Listener;

class MonsterQuestListener implements Listener {

    /**
     * @priority MONITOR
     */
    public function handleMonsterDeathEvent(MonsterDeathEvent $event) : void {
        $player  = $event->getPlayer();
        $monster = $event->getMonster();

        $type = match(true) {
            $monster instanceof Cow     => MonsterKillMission::TYPE_COW,
            $monster instanceof Pig     => MonsterKillMission::TYPE_PIG,
            $monster instanceof Chicken => MonsterKillMission::TYPE_CHICKEN,
            $monster instanceof Sheep   => MonsterKillMission::TYPE_SHEEP,
            $monster instanceof Crab    => MonsterKillMission::TYPE_CRAB,
            default                     => null
        };

        if ($type === null) {
            return;
        }

        Quests::getInstance()->handleMonsterKill($player, $type);
    }
}
