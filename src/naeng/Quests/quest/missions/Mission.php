<?php

namespace naeng\Quests\quest\missions;

use naeng\Quests\quest\Quest;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;

abstract class Mission{

    public const NAME = "미션";
    public const DEFAULT_PROGRESS = null;

    public function __construct(protected array $playerData = [], protected ?Quest $quest = null){}

    final function getName() : string{
        return self::NAME;
    }

    public function getProgress(Player|string $player, mixed $default = null) : mixed{
        return ($this->playerData[strtolower($player instanceof Player ? $player->getName() : $player)] ?? $default);
    }

    abstract public function currentProgress(Player|string $player) : string;

    abstract public function getInformation() : string;

    abstract public function isCleared(Player|string $player) : bool;

    public function getQuest() : ?Quest{
        return $this->quest;
    }

    public function setQuest(Quest $quest) : void{
        $this->quest = $quest;
    }

    public function isTrying(Player|string $player) : bool{
        return $this->isDataExist($player) && !$this->isCleared($player);
    }

    public function isDataExist(Player|string $player) : bool{
        return isset($this->playerData[strtolower($player instanceof Player ? $player->getName() : $player)]);
    }

    public function setProgress(Player|string $player, mixed $value) : void{
        $this->playerData[strtolower($player instanceof Player ? $player->getName() : $player)] = $value;
    }

    public function deleteProgress(Player|string $player) : void{
        unset($this->playerData[strtolower($player instanceof Player ? $player->getName() : $player)]);
    }

    public function reset() : void{
        $this->playerData = [];
    }

    final public function getPlayerData() : array{
        return $this->playerData;
    }

    public function equals(Mission $mission) : bool{
        return $this->getName() === $mission->getName() && $this->playerData === $mission->getPlayerData();
    }

    abstract public function jsonSerialize() : array;

    abstract public static function jsonDeserialize(array $jsonSerializedMission) : self;

    public function handleBlockBreakEvent(BlockBreakEvent $event) : void{}

    public function handlePlayerChatEvent(PlayerChatEvent $event) : void{}

    public function handleCommandEvent(CommandEvent $event) : void{}

    public function handleEntityDamageByEntityEvent(EntityDamageByEntityEvent $event) : void{}

}