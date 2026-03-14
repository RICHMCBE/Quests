<?php

namespace naeng\quests\quest;

use naeng\quests\quest\missions\defaults\CommandMission;
use naeng\quests\quest\missions\defaults\FishCatchMission;
use naeng\quests\quest\missions\defaults\OreMineMission;
use naeng\quests\quest\missions\defaults\PlantCropMission;
use naeng\quests\quest\missions\defaults\ShopSellMission;
use naeng\quests\quest\missions\defaults\SpecificCropMission;
use naeng\quests\quest\missions\defaults\ToolUpgradeMission;
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
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 1: 섬 시작하기
        // ─────────────────────────────────────────────
        $guide1 = new Quest("guide_1", "섬 시작하기", Quest::TYPE_GUIDE);
        $guide1->addMission(new CommandMission("섬", "/섬 명령어 사용하기"));

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 2: 광산 탐험하기
        // ─────────────────────────────────────────────
        $guide2 = new Quest("guide_2", "광산 탐험하기", Quest::TYPE_GUIDE);
        $guide2->addMission(new CommandMission("광산", "/광산으로 이동하기"));
        $guide2->addMission(new OreMineMission(OreMineMission::ORE_STONE,   32));
        $guide2->addMission(new OreMineMission(OreMineMission::ORE_COAL,     8));
        $guide2->addMission(new OreMineMission(OreMineMission::ORE_IRON,     4));
        $guide2->addMission(new OreMineMission(OreMineMission::ORE_GOLD,     4));
        $guide2->addMission(new OreMineMission(OreMineMission::ORE_DIAMOND,  1));
        $guide2->addMission(new OreMineMission(OreMineMission::ORE_EMERALD,  1));

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 3: 광물 판매하기
        // 연동: 상점 플러그인에서 Quests::getInstance()->handleShopSell($player, "광물상점") 호출 필요
        // ─────────────────────────────────────────────
        $guide3 = new Quest("guide_3", "광물 판매하기", Quest::TYPE_GUIDE);
        $guide3->addMission(new CommandMission("상점", "/상점 명령어 사용하기"));
        $guide3->addMission(new ShopSellMission("광물상점", 1));

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 4: 작물 심어보기
        // ─────────────────────────────────────────────
        $guide4 = new Quest("guide_4", "작물 심어보기", Quest::TYPE_GUIDE);
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_WHEAT,   4));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_CARROT,  4));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_POTATO,  4));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_MELON,   1));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_PUMPKIN, 1));

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 5: 작물 수확해보기
        // ─────────────────────────────────────────────
        $guide5 = new Quest("guide_5", "작물 수확해보기", Quest::TYPE_GUIDE);
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_WHEAT,   4));
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_CARROT,  4));
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_POTATO,  4));
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_MELON,   1)); // 수박 1개 수확 (= 수박조각 3개 이상)
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_PUMPKIN, 1));

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 6: 도구 강화해보기
        // 연동: ToolCore에서 Quests::getInstance()->handleToolUpgrade($player) 호출 필요
        // ─────────────────────────────────────────────
        $guide6 = new Quest("guide_6", "도구 강화해보기", Quest::TYPE_GUIDE);
        $guide6->addMission(new CommandMission("강화", "/강화 명령어 사용하기"));
        $guide6->addMission(new ToolUpgradeMission(1));

        return [
            $guide1,
            $guide2,
            $guide3,
            $guide4,
            $guide5,
            $guide6,
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

    /**
     * FishPlugin이 로드된 경우에만 등록되는 낚시 퀘스트
     * @return Quest[]
     */
    public static function getFishQuests() : array{
        // 일일 퀘스트: 물고기 10마리 낚기
        $dailyFish = new Quest("daily_fish", "오늘의 낚시", Quest::TYPE_DAILY);
        $dailyFish->addMission(new FishCatchMission(10));

        // 일반 퀘스트: 물고기 50마리 낚기
        $normalFish = new Quest("normal_fish_1", "낚시 입문", Quest::TYPE_NORMAL);
        $normalFish->addMission(new FishCatchMission(50));

        return [
            $dailyFish,
            $normalFish
        ];
    }

    /**
     * 보스 레이드 퀘스트 - 비활성화됨
     * @return Quest[]
     */
    public static function getBossQuests() : array{
        return [];
    }

}
