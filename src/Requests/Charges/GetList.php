<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Charges;

use MountBit\PagueDev\Responses\Charges\GetList as ListResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetList extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ListResponse::class;

    public function __construct(
        public readonly ?int $page = null,
        public readonly ?int $limit = null,
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $projectId = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/charges';
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

        if (! empty($this->status)) {
            $query['status'] = $this->status;
        }

        if (! empty($this->projectId)) {
            $query['projectId'] = $this->projectId;
        }

        return $query;
    }
}
