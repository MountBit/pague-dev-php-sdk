<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Projects;

use MountBit\PagueDev\Responses\Projects\GetById as GetByIdResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetById extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = GetByIdResponse::class;

    public function __construct(
        public readonly string $id,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/projects/'.rawurlencode($this->id);
    }
}
