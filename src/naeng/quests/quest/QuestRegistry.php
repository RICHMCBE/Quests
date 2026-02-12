<?php

namespace naeng\quests\quest;

use naeng\quests\quest\missions\defaults\ChatMission;
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

        // 가이드 퀘스트 2: 인사하기
        $guide2 = new Quest("guide_2", "인사하기", Quest::TYPE_GUIDE);
        $guide2->addMission(new ChatMission("!!안녕하세요", "전체채팅으로 인사하기"));

        return [
            $guide1,
            $guide2
        ];
    }

    /**
     * @return Quest[]
     */
    public static function getNormalQuests() : array{
        // 일반 퀘스트 1: 비트 수확하기
        $normal1 = new Quest("normal_1", "비트 수확하기", Quest::TYPE_NORMAL);
        $normal1->addMission(new SpecificCropMission(SpecificCropMission::CROP_BEETROOT, 1));

        return [
            $normal1
        ];
    }

}
