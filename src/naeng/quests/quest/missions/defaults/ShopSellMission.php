<?php

declare(strict_types=1);

namespace naeng\quests\quest\missions\defaults;

use naeng\quests\quest\missions\Mission;
use pocketmine\player\Player;

/**
 * 상점에서 아이템을 판매하면 진행되는 미션.
 *
 * 외부 상점 플러그인에서 판매가 완료되면 Quests::getInstance()->handleShopSell($player, $shopId) 를 호출해야 합니다.
 */
class ShopSellMission extends Mission{

    public const NAME = "상점 판매하기";

    public function __construct(
        private readonly string $shopId,
        private readonly int    $count = 1
    ){
    }

    public function getName() : string{
        return self::NAME;
    }

    public function getShopId() : string{
        return $this->shopId;
    }

    public function getCount() : int{
        return $this->count;
    }

    public function getInformation() : string{
        return "{$this->shopId}에서 판매하기";
    }

    public function currentProgress(Player|string $player) : string{
        $progress = $this->getProgress($player) ?? 0;
        $cleared  = $this->isCleared($player) ? "§a(완료)" : "";
        return "{$this->shopId} {$progress}/{$this->count}회 판매 {$cleared}";
    }

    public function isCleared(Player|string $player) : bool{
        return ($this->getProgress($player) ?? 0) >= $this->count;
    }

    /**
     * 상점 플러그인에서 해당 shopId 판매 완료 시 호출
     */
    public function handleSell(Player $player) : void{
        if(!$this->isTrying($player)){
            if($this->quest !== null && $this->quest->isAutoAccept() && !$this->quest->isCleared($player)){
                $this->setProgress($player, self::DEFAULT_PROGRESS);
            }else{
                return;
            }
        }

        if($this->isCleared($player)){
            return;
        }

        $progress    = $this->getProgress($player) ?? 0;
        $newProgress = $progress + 1;
        $this->setProgress($player, $newProgress);

        if($this->quest !== null){
            $questName = $this->quest->getDisplayName();
            if($newProgress >= $this->count){
                $player->sendPopup("§r丌 §f{$this->getInformation()}");
            }else{
                $player->sendPopup("§r不 §f{$this->getInformation()} §7- §e{$newProgress}§7/§f{$this->count}");
            }
            $this->quest->clearCheck($player);
        }
    }
}
