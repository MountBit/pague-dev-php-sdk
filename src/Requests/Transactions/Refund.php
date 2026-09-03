<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Transactions;

use MountBit\PagueDev\Responses\Transactions\Refund as RefundResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Refund extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected ?string $response = RefundResponse::class;

    public function __construct(
        public readonly string $id,
        protected readonly ?string $reason = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/transactions/'.rawurlencode($this->id).'/refund';
    }

    public function defaultBody(): array
    {
        $data = [];

        if (! empty($this->reason)) {
            $data['reason'] = $this->reason;
        }

        return $data;
    }
}
