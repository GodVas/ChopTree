<?php

namespace skh6075\choptree;

use pocketmine\block\Block;
use pocketmine\block\Leaves;
use pocketmine\block\Wood;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\particle\DestroyBlockParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

final class Loader extends PluginBase implements Listener{

    protected function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    private function isCheckConsistent(Block $block, Item $item): bool{
        return $block instanceof Wood and $item instanceof Axe;
    }

    private function onPlayerBlockBreak(Player $player, Block $block, Item $item): array{
        $block->onBreak($item);
        $block->getPos()->getWorld()->addParticle($block->getPos(), new DestroyBlockParticle($block));

        return $block->getDrops($item);
    }

    /** @priority MONITOR */
    public function onBlockBreak(BlockBreakEvent $event): void{
        if ($event->isCancelled())
            return;

        $block = $event->getBlock();
        $item = $event->getItem();
        $player = $event->getPlayer();
        if (!$this->isCheckConsistent($block, $item))
            return;

        $nowY = $block->getPos()->getY();
        $maxY = World::Y_MAX - $nowY;
        for ($y = $nowY+1; $y < $maxY; $y ++) {
            $block_ = $block->getPos()->getWorld()->getBlockAt($block->getPos()->getX(), $y, $block->getPos()->getZ());
            if (!$block_ instanceof Wood)
                break;

            $this->onLeaveDestroy($player, $block_->getPos(), $item);
            foreach ($this->onPlayerBlockBreak($player, $block, $item) as $value)
                $block->getPos()->getWorld()->dropItem($block->getPos(), $value);
        }
    }

    private function onLeaveDestroy(Player $player, Position $position, Item $item): void{
        foreach ($this->getNearByBlocks($position, 2) as $block) {
            if (!$block instanceof Leaves)
                continue;

            $this->startBreakSideLeave($player, $block->getPos(), $item);
            foreach ($this->onPlayerBlockBreak($player, $block, $item) as $value)
                $block->getPos()->getWorld()->dropItem($block->getPos(), $value);
        }
    }

    private function startBreakSideLeave(Player $player, Position $position, Item $item): void{
        foreach (Facing::HORIZONTAL as $side) {
            $block = $position->getWorld()->getBlock($position);
            $side = $block->getSide($side);
            if (!$side instanceof Leaves)
                continue;

            $this->startBreakSideLeave($player, $side->getPos(), $item);
            foreach ($this->onPlayerBlockBreak($player, $side, $item) as $value)
                $side->getPos()->getWorld()->dropItem($side->getPos(), $value);
        }
    }

    /** @return Block[] */
    private function getNearByBlocks(Position $position, int $radius): array{
        $result = [];

        for ($x = -$radius; $x < $radius; $x ++) {
            for ($z = -$radius; $z < $radius; $z++) {
                $block = $position->getWorld()->getBlock($position->add($x, 0, $z));
                $result[] = clone $block;
            }
        }

        return $result;
    }
}
