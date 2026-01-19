<?php

namespace naeng\quests\quest;

use naeng\quests\quest\missions\defaults\CommandMission;
use naeng\quests\quest\missions\defaults\SpecificCropMission;
use naeng\quests\quest\missions\defaults\VoteMission;

class QuestRegistry{

    /**
     * @return Quest[]
     */
    public static function getQuests() : array{
        return [
            ...self::getDailyQuests(),
            ...self::getGuideQuests(),
            ...self::getNormalQuests()
        ];
    }

    /**
     * @return Quest[]
     */
    public static function getDailyQuests() : array{
        $daily = new Quest("daily", "일일 퀘스트", Quest::TYPE_DAILY);
        $daily->addMission(new VoteMission());
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_WHEAT, 256));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_CARROT, 256));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_POTATO, 256));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_BEETROOT, 256));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_NETHER_WART, 128));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_MELON, 128));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_PUMPKIN, 128));
        $daily->addMission(new SpecificCropMission(SpecificCropMission::CROP_SUGARCANE, 256));

        return [
            $daily
        ];
    }

    /**
     * @return Quest[]
     */
    public static function getGuideQuests() : array{
        // 가이드 퀘스트 1: 섬 시작하기
        $guide1 = new Quest("guide_1", "섬 시작하기", Quest::TYPE_GUIDE);
        $guide1->addMission(new CommandMission("섬", "/섬 명령어 사용하기"));

        return [
            $guide1
        ];
    }

    /**
     * @return Quest[]
     */
    public static function getNormalQuests() : array{
        return [];
    }

}
