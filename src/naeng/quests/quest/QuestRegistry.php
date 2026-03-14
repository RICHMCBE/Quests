<?php

namespace naeng\quests\quest;

use cherrychip\EnchantBook\api\EnchantBookAPI;
use naeng\quests\quest\missions\defaults\CommandMission;
use naeng\quests\quest\missions\defaults\FishCatchMission;
use naeng\quests\quest\missions\defaults\OreMineMission;
use naeng\quests\quest\missions\defaults\PlantCropMission;
use naeng\quests\quest\missions\defaults\ShopSellMission;
use naeng\quests\quest\missions\defaults\SpecificCropMission;
use naeng\quests\quest\missions\defaults\ToolUpgradeMission;
use naeng\quests\quest\missions\defaults\VoteMission;
use pocketmine\item\VanillaItems;

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
        // 보상: 철 곡괭이 1개 (광산 탐험 준비) + 골드 2,000
        // 단순 진입 퀘스트 - 소액 지급
        $guide1->setRewardItems([
            VanillaItems::IRON_PICKAXE()->setCount(1)
        ]);
        $guide1->setRewardGold(57000);

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
        // 보상: 빵 4개 (상점 판매 전 배고픔 해소) + 골드 59,000
        // 채굴 요구량 판매가(조약돌32×1 + 석탄8×3 + 철4×5 + 금4×6 + 다이아1×10 + 에메랄드1×20 = 130원)
        // 실제 채굴량 약 500원 + 씨앗 구매 시작 자금 역할
        $guide2->setRewardItems([
            VanillaItems::BREAD()->setCount(4)
        ]);
        $guide2->setRewardGold(59000);

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 3: 광물 판매하기
        // 연동: 상점 플러그인에서 Quests::getInstance()->handleShopSell($player, "광물상점") 호출 필요
        // ─────────────────────────────────────────────
        $guide3 = new Quest("guide_3", "광물 판매하기", Quest::TYPE_GUIDE);
        $guide3->addMission(new CommandMission("상점", "/상점 명령어 사용하기"));
        $guide3->addMission(new ShopSellMission("광물 상점", 1));
        // 보상: 씨앗류 소량 (작물 심기 준비) + 골드 66,000
        // 씨앗 아이템 가치: 밀씨앗4×250 + 당근4×150 + 감자4×150 + 수박씨1×900 + 호박씨1×750 = 3,850원
        // 골드 포함 총 8,850원 → 추가 씨앗 구매 지원 역할
        $guide3->setRewardItems([
            VanillaItems::WHEAT_SEEDS()->setCount(4),
            VanillaItems::CARROT()->setCount(4),
            VanillaItems::POTATO()->setCount(4),
            VanillaItems::MELON_SEEDS()->setCount(1),
            VanillaItems::PUMPKIN_SEEDS()->setCount(1),
        ]);
        $guide3->setRewardGold(66000);

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 4: 작물 심어보기
        // ─────────────────────────────────────────────
        $guide4 = new Quest("guide_4", "작물 심어보기", Quest::TYPE_GUIDE);
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_WHEAT,   4));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_CARROT,  4));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_POTATO,  4));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_MELON,   1));
        $guide4->addMission(new PlantCropMission(PlantCropMission::PLANT_PUMPKIN, 1));
        // 보상: 뼛가루 8개 (작물 성장 촉진용) + 골드 55,000
        // 씨앗이 이미 guide_3에서 지급되므로 소액 유지
        $guide4->setRewardItems([
            VanillaItems::BONE_MEAL()->setCount(8)
        ]);
        $guide4->setRewardGold(55000);

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 5: 작물 수확해보기
        // ─────────────────────────────────────────────
        $guide5 = new Quest("guide_5", "작물 수확해보기", Quest::TYPE_GUIDE);
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_WHEAT,   4));
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_CARROT,  4));
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_POTATO,  4));
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_MELON,   1)); // 수박 1개 수확 (= 수박조각 3개 이상)
        $guide5->addMission(new SpecificCropMission(SpecificCropMission::CROP_PUMPKIN, 1));
        // 보상: 효율 주문서 1개 (도구 강화 준비) - EnchantBook 연동 + 골드 62,000
        // 수확 최소 판매가: 밀4×48 + 당근4×40 + 감자4×40 + 수박1×88 + 호박1×77 = 677원
        // 골드 62,000원은 강화 재료 구매 지원
        if(class_exists(EnchantBookAPI::class)){
            $enchantBook = EnchantBookAPI::getInstance()->createEnchantBook("효율", 3, 70, 1);
            if($enchantBook !== null){
                $guide5->setRewardItems([$enchantBook]);
            }
        }
        $guide5->setRewardGold(62000);

        // ─────────────────────────────────────────────
        // 가이드 퀘스트 6: 도구 강화해보기
        // 연동: ToolCore에서 Quests::getInstance()->handleToolUpgrade($player) 호출 필요
        // ─────────────────────────────────────────────
        $guide6 = new Quest("guide_6", "도구 강화해보기", Quest::TYPE_GUIDE);
        $guide6->addMission(new CommandMission("강화", "/강화 명령어 사용하기"));
        $guide6->addMission(new ToolUpgradeMission(1));
        // 보상: 낚싯대 1개 (낚시 즐기기 준비) + 골드 59,000
        // 강화 완료로 도구 효율 향상 → 장기 채굴 수익 증가
        $guide6->setRewardItems([
            VanillaItems::FISHING_ROD()->setCount(1)
        ]);
        $guide6->setRewardGold(59000);

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
     * FishPlugin이 로드된 경우에만 등록되는 낚시 가이드 퀘스트
     * @return Quest[]
     */
    public static function getFishGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 7: 낚시 즐기기
        // ─────────────────────────────────────────────
        $guide7 = new Quest("guide_7", "낚시 즐기기", Quest::TYPE_GUIDE);
        $guide7->addMission(new CommandMission("낚시터", "/낚시터로 이동하기"));
        $guide7->addMission(new FishCatchMission(10));
        $guide7->addMission(new CommandMission("상점", "/상점으로 이동하기"));
        $guide7->addMission(new ShopSellMission("낚시상점", 1));
        // 보상: 골드 75,000
        // 낚시 판매 경험 완료 보너스
        $guide7->setRewardGold(75000);

        return [$guide7];
    }

    /**
     * 보스 레이드 퀘스트 - 비활성화됨
     * @return Quest[]
     */
    public static function getBossQuests() : array{
        return [];
    }

}
