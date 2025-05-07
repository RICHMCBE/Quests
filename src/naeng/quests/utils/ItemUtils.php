<?php

namespace naeng\quests\utils;

use naeng\goodies\libs\kim\present\utils\itemserialize\SnbtItemSerializer;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class ItemUtils{

    public static function serialize(Item $item) : ?string{
        if($item->isNull()){
            return null;
        }

        return SnbtItemSerializer::serialize($item);
    }

    public static function deserialize(?string $serializedItem) : Item{
        if($serializedItem === null){
            return VanillaItems::AIR();
        }

        return SnbtItemSerializer::deserialize($serializedItem);
    }

    /** @param Item[] $items */
    public static function serializeList(array $items) : ?string{
        foreach($items as $index => $item){
            if($item->isNull()){
                unset($items[$index]);
            }
        }

        return SnbtItemSerializer::serializeList($items);
    }

    public static function deserializeList(string $serializedItemList) : array{
        return SnbtItemSerializer::deserializeList($serializedItemList);
    }

}