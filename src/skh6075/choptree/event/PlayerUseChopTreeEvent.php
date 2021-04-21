<?php

namespace skh6075\choptree\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

class PlayerUseChopTreeEvent extends Event implements Cancellable{
    use CancellableTrait;

    public Player $player;

    public function __construct(Player $player) {
        $this->player = $player;
    }

    final public function getPlayer(): Player{
        return $this->player;
    }
}