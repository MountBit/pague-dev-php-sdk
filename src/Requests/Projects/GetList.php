<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Projects;

use MountBit\PagueDev\Responses\Projects\GetList as ListResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetList extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ListResponse::class;

    public function __construct(
        public readonly ?int $page = null,
        public readonly ?int $limit = null,
        public readonly ?string $search = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/projects';
    }

    public function defaultQuery(): array
    {
        $query = [];

        if (! empty($this->page)) {
            $query['page'] = $this->page;
        }

        if (! empty($this->limit)) {
            $query['limit'] = $this->limit;
        }

        if (! empty($this->search)) {
            $query['search'] = $this->search;
        }

        return $query;
    }
}
