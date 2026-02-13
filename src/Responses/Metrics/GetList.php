<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Metrics;

use Saloon\Http\Response;

class GetList extends Response
{
    public function getTotalRevenue(): float
    {
        return $this->json('totalRevenue');
    }

    public function getCurrentMrr(): float
    {
        return $this->json('currentMrr');
    }

    public function getGroupedByDay(): array
    {
        return $this->json('groupedByDay');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
