<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\BalanceBlocks;

use MountBit\PagueDev\Dtos\BalanceBlock;
use Saloon\Http\Response;

class GetList extends Response
{
    /**
     * @return array<BalanceBlock>
     */
    public function getItems(): array
    {
        return array_map(
            fn (array $item) => BalanceBlock::fromArray($item),
            $this->json('items') ?: []
        );
    }

    public function getTotal(): int
    {
        return (int) $this->json('total');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
