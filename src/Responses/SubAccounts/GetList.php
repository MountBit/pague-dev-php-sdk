<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\SubAccounts;

use MountBit\PagueDev\Dtos\SubAccount;
use Saloon\Http\Response;

class GetList extends Response
{
    /**
     * @return array<SubAccount>
     */
    public function getData(): array
    {
        return array_map(
            fn (array $item) => SubAccount::fromArray($item),
            $this->json('data') ?: []
        );
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
