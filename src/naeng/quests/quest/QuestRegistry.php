<?php

namespace naeng\quests\quest;

use cherrychip\EnchantBook\api\EnchantBookAPI;
use naeng\quests\quest\missions\defaults\AttendanceClaimMission;
use naeng\quests\quest\missions\defaults\CommandMission;
use naeng\quests\quest\missions\defaults\DivingMineAcquireMission;
use naeng\quests\quest\missions\defaults\ExchangeBuyMission;
use naeng\quests\quest\missions\defaults\FishCatchMission;
use naeng\quests\quest\missions\defaults\OreMineMission;
use naeng\quests\quest\missions\defaults\PlantCropMission;
use naeng\quests\quest\missions\defaults\PlayTimeMission;
use naeng\quests\quest\missions\defaults\RankUpgradeMission;
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

        // ─── 고정 미션 1: 접속시간 90분 ───
        $daily->addMission(new PlayTimeMission(90));

        // ─── 고정 미션 2: 서버 추천 2회 ───
        $daily->addMission(new VoteMission(2));

        // ─── 랜덤 미션 4개 (매일 달라짐) ───
        $pool = self::buildRandomMissionPool();
        shuffle($pool);
        foreach(array_slice($pool, 0, 4) as $mission){
            $daily->addMission($mission);
        }

        return [$daily];
    }

    /**
     * 매일 랜덤으로 4개를 뽑을 미션 풀 (수량도 랜덤)
     *
     * @return \naeng\quests\quest\missions\Mission[]
     */
    private static function buildRandomMissionPool() : array{
        return [
            // 작물 수확
            new SpecificCropMission(SpecificCropMission::CROP_WHEAT,       mt_rand(128, 512)),
            new SpecificCropMission(SpecificCropMission::CROP_CARROT,      mt_rand(128, 512)),
            new SpecificCropMission(SpecificCropMission::CROP_POTATO,      mt_rand(128, 512)),
            new SpecificCropMission(SpecificCropMission::CROP_BEETROOT,    mt_rand(64,  256)),
            new SpecificCropMission(SpecificCropMission::CROP_NETHER_WART, mt_rand(64,  256)),
            new SpecificCropMission(SpecificCropMission::CROP_MELON,       mt_rand(64,  256)),
            new SpecificCropMission(SpecificCropMission::CROP_PUMPKIN,     mt_rand(64,  256)),
            new SpecificCropMission(SpecificCropMission::CROP_SUGARCANE,   mt_rand(128, 512)),
            // 광석 채굴
            new OreMineMission(OreMineMission::ORE_STONE,   mt_rand(32,  128)),
            new OreMineMission(OreMineMission::ORE_COAL,    mt_rand(16,   64)),
            new OreMineMission(OreMineMission::ORE_IRON,    mt_rand(8,    32)),
            new OreMineMission(OreMineMission::ORE_GOLD,    mt_rand(4,    16)),
            new OreMineMission(OreMineMission::ORE_DIAMOND, mt_rand(2,     8)),
            new OreMineMission(OreMineMission::ORE_EMERALD, mt_rand(1,     4)),
            // 작물 심기
            new PlantCropMission(PlantCropMission::PLANT_WHEAT,   mt_rand(64, 256)),
            new PlantCropMission(PlantCropMission::PLANT_CARROT,  mt_rand(64, 256)),
            new PlantCropMission(PlantCropMission::PLANT_POTATO,  mt_rand(64, 256)),
            new PlantCropMission(PlantCropMission::PLANT_MELON,   mt_rand(32, 128)),
            new PlantCropMission(PlantCropMission::PLANT_PUMPKIN, mt_rand(32, 128)),
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
        $guide3->addMission(new ShopSellMission("광물상점", 1));
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
        // 연동: EnchantBook 강화 성공 시 Quests::getInstance()->handleToolUpgrade($player) 호출
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
     * DivingMine 플러그인이 로드된 경우에만 등록되는 잠수광산 가이드 퀘스트
     * @return Quest[]
     */
    public static function getDivingMineGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 8: 잠수광산 도전하기
        // 연동: DivingMine 플러그인 필요 (/잠광 명령어)
        // 잠수광산은 채굴이 아닌 아이템 수집 컨텐츠
        // ─────────────────────────────────────────────
        $guide8 = new Quest("guide_8", "잠수광산 도전하기", Quest::TYPE_GUIDE);
        $guide8->addMission(new CommandMission("잠광", "/잠광 명령어 사용하기"));
        $guide8->addMission(new DivingMineAcquireMission(5));
        $guide8->setRewardGold(70000);

        return [$guide8];
    }

    /**
     * NeighborPlugin이 로드된 경우에만 등록되는 길드 가이드 퀘스트
     * @return Quest[]
     */
    public static function getNeighborhoodGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 9: 길드 가입하기
        // 연동: NeighborPlugin 필요 (/길드 명령어)
        // ─────────────────────────────────────────────
        $guide9 = new Quest("guide_9", "길드 가입하기", Quest::TYPE_GUIDE);
        $guide9->addMission(new CommandMission("길드", "/길드 명령어 사용하기"));
        $guide9->setRewardGold(50000);

        return [$guide9];
    }

    /**
     * AttendanceCheck 플러그인이 로드된 경우에만 등록되는 출석 가이드 퀘스트
     * @return Quest[]
     */
    public static function getAttendanceGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 10: 출석 체크하기
        // 연동: AttendanceCheck 플러그인 필요 (/출석체크 명령어)
        // ─────────────────────────────────────────────
        $guide10 = new Quest("guide_10", "출석 체크하기", Quest::TYPE_GUIDE);
        $guide10->addMission(new CommandMission("출석체크", "/출석체크 명령어 사용하기"));
        $guide10->addMission(new AttendanceClaimMission(1));
        $guide10->setRewardGold(30000);

        return [$guide10];
    }

    /**
     * RankPrefix 플러그인이 로드된 경우에만 등록되는 랭크 가이드 퀘스트
     * @return Quest[]
     */
    public static function getRankGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 11: 랭크 업그레이드하기
        // 연동: RankPrefix 플러그인 필요 (/랭크 명령어)
        // Stone 랭크 달성 시 창고 이용권이 보상으로 지급됨 → guide_12 창고로 연결
        // ─────────────────────────────────────────────
        $guide11 = new Quest("guide_11", "랭크 업그레이드하기", Quest::TYPE_GUIDE);
        $guide11->addMission(new CommandMission("랭크", "/랭크 명령어 사용하기"));
        $guide11->addMission(new RankUpgradeMission(1));
        $guide11->setRewardGold(50000);

        return [$guide11];
    }

    /**
     * Warehouse 플러그인이 로드된 경우에만 등록되는 창고 가이드 퀘스트
     * @return Quest[]
     */
    public static function getWarehouseGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 12: 창고 사용하기
        // 연동: Warehouse 플러그인 필요 (/창고 명령어)
        // 창고 이용권은 Stone 랭크(guide_11) 달성 보상으로 지급됨
        // ─────────────────────────────────────────────
        $guide12 = new Quest("guide_12", "창고 사용하기", Quest::TYPE_GUIDE);
        $guide12->addMission(new CommandMission("창고", "/창고 명령어 사용하기"));
        $guide12->setRewardGold(20000);

        return [$guide12];
    }

    /**
     * UserExchange 플러그인이 로드된 경우에만 등록되는 거래소 가이드 퀘스트
     * @return Quest[]
     */
    public static function getExchangeGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 13: 거래소 이용하기
        // 연동: UserExchange 플러그인 필요 (/거래소 명령어)
        // ─────────────────────────────────────────────
        $guide13 = new Quest("guide_13", "거래소 이용하기", Quest::TYPE_GUIDE);
        $guide13->addMission(new CommandMission("거래소", "/거래소 명령어 사용하기"));
        $guide13->addMission(new ExchangeBuyMission(1));
        $guide13->setRewardGold(40000);

        return [$guide13];
    }

    /**
     * NeighborhoodShop 플러그인이 로드된 경우에만 등록되는 길드상점 가이드 퀘스트
     * @return Quest[]
     */
    public static function getNeighborhoodShopGuideQuests() : array{
        // ─────────────────────────────────────────────
        // 가이드 퀘스트 14: 길드상점 이용하기
        // 연동: NeighborhoodShop 플러그인 필요 (/길드상점 명령어)
        // guide_9 길드 가입 이후 자연스럽게 연결
        // ─────────────────────────────────────────────
        $guide14 = new Quest("guide_14", "길드상점 이용하기", Quest::TYPE_GUIDE);
        $guide14->addMission(new CommandMission("길드상점", "/길드상점 명령어 사용하기"));
        $guide14->setRewardGold(30000);

        return [$guide14];
    }

    /**
     * 보스 레이드 퀘스트 - 비활성화됨
     * @return Quest[]
     */
    public static function getBossQuests() : array{
        return [];
    }

}
