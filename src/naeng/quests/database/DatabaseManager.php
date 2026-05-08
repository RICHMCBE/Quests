<?php

namespace naeng\quests\database;

use Generator;
use naeng\quests\Quests;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;

class DatabaseManager{

    private DataConnector $database;

    public function __construct(DataConnector $database){
        $this->database = $database;
        $this->initialize();
    }

    private function initialize() : void{
        Await::f2c(function() : Generator{
            yield from $this->database->asyncGeneric("init");
            Quests::getInstance()->getLogger()->info("Database initialized");
        });
    }

    public function getDatabase() : DataConnector{
        return $this->database;
    }

    /**
     * 진행 데이터 저장
     */
    public function saveProgress(string $playerName, string $questId, int $missionIndex, int $progress) : void{
        Await::f2c(function() use($playerName, $questId, $missionIndex, $progress) : Generator{
            yield from $this->database->asyncInsert("progress.save", [
                "player_name" => strtolower($playerName),
                "quest_id" => $questId,
                "mission_index" => $missionIndex,
                "progress" => $progress
            ]);
        });
    }

    /**
     * 진행 데이터 로드
     * @return Generator<array<int, int>>
     */
    public function loadProgress(string $playerName, string $questId) : Generator{
        $rows = yield from $this->database->asyncSelect("progress.load", [
            "player_name" => strtolower($playerName),
            "quest_id" => $questId
        ]);

        $progress = [];
        foreach($rows as $row){
            $progress[(int)$row["mission_index"]] = (int)$row["progress"];
        }
        return $progress;
    }

    /**
     * 진행 데이터 삭제
     */
    public function deleteProgress(string $playerName, string $questId) : void{
        Await::f2c(function() use($playerName, $questId) : Generator{
            yield from $this->database->asyncGeneric("progress.delete", [
                "player_name" => strtolower($playerName),
                "quest_id" => $questId
            ]);
        });
    }

    /**
     * 일일 퀘스트 진행 데이터 리셋
     */
    public function resetDailyProgress() : void{
        Await::f2c(function() : Generator{
            yield from $this->database->asyncGeneric("progress.reset.daily");
            Quests::getInstance()->getLogger()->info("Daily quest progress has been reset");
        });
    }

    /**
     * 클리어 데이터 저장
     */
    public function saveCleared(string $playerName, string $questId) : void{
        $today = date('Y-m-d');
        Await::f2c(function() use($playerName, $questId, $today) : Generator{
            yield from $this->database->asyncInsert("cleared.save", [
                "player_name" => strtolower($playerName),
                "quest_id" => $questId,
                "cleared_date" => $today
            ]);
        });
    }

    /**
     * 클리어 여부 확인 (오늘)
     * @return Generator<bool>
     */
    public function isClearedToday(string $playerName, string $questId) : Generator{
        $today = date('Y-m-d');
        $rows = yield from $this->database->asyncSelect("cleared.check.today", [
            "player_name" => strtolower($playerName),
            "quest_id" => $questId,
            "cleared_date" => $today
        ]);
        return ($rows[0]["cnt"] ?? 0) > 0;
    }

    /**
     * 클리어 여부 확인 (전체)
     * @return Generator<bool>
     */
    public function isClearedEver(string $playerName, string $questId) : Generator{
        $rows = yield from $this->database->asyncSelect("cleared.check.ever", [
            "player_name" => strtolower($playerName),
            "quest_id" => $questId
        ]);
        return ($rows[0]["cnt"] ?? 0) > 0;
    }

    /**
     * 플레이어의 모든 클리어 데이터 로드
     * @return Generator<array<string, bool>>
     */
    public function loadAllCleared(string $playerName) : Generator{
        $today = date('Y-m-d');
        $rows = yield from $this->database->asyncSelect("cleared.load.all", [
            "player_name" => strtolower($playerName)
        ]);

        $cleared = [];
        foreach($rows as $row){
            $questId = $row["quest_id"];
            $clearedDate = $row["cleared_date"];

            // 일일 퀘스트는 오늘 클리어한 것만
            if(str_starts_with($questId, "daily_")){
                if($clearedDate === $today){
                    $cleared[$questId] = true;
                }
            }else{
                $cleared[$questId] = true;
            }
        }
        return $cleared;
    }

    public function close() : void{
        $this->database->close();
    }

    /**
     * 보상 아이템 저장
     */
    public function saveRewards(string $questId, string $itemData) : void{
        Await::f2c(function() use($questId, $itemData) : Generator{
            yield from $this->database->asyncInsert("rewards.save", [
                "quest_id" => $questId,
                "item_data" => $itemData
            ]);
        });
    }

    /**
     * 보상 아이템 로드
     * @return Generator<string|null>
     */
    public function loadRewards(string $questId) : Generator{
        $rows = yield from $this->database->asyncSelect("rewards.load", [
            "quest_id" => $questId
        ]);
        return $rows[0]["item_data"] ?? null;
    }

    /**
     * 모든 보상 아이템 로드
     * @return Generator<array<string, string>>
     */
    public function loadAllRewards() : Generator{
        $rows = yield from $this->database->asyncSelect("rewards.load.all", []);
        $rewards = [];
        foreach($rows as $row){
            $rewards[$row["quest_id"]] = $row["item_data"];
        }
        return $rewards;
    }

    /**
     * 보상 아이템 삭제
     */
    public function deleteRewards(string $questId) : void{
        Await::f2c(function() use($questId) : Generator{
            yield from $this->database->asyncGeneric("rewards.delete", [
                "quest_id" => $questId
            ]);
        });
    }
}
