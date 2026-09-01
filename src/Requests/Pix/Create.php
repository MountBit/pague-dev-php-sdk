<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Pix;

use MountBit\PagueDev\Concerns\HasIdempotencyKey;
use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Dtos\Pix\SplitAllocation;
use MountBit\PagueDev\Responses\Pix\Create as CreateResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Create extends Request implements HasBody
{
    use HasIdempotencyKey;
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected ?string $response = CreateResponse::class;

    /**
     * @param  array<SplitAllocation>  $split
     */
    public function __construct(
        protected readonly float $amount,
        protected readonly string $description,
        protected readonly ?Customer $customer = null,
        protected readonly ?string $projectId = null,
        protected readonly ?string $pspCredentialId = null,
        protected readonly ?int $expiresIn = null,
        protected readonly ?string $externalReference = null,
        protected readonly ?array $metadata = null,
        protected readonly array $split = [],
        protected readonly ?string $idempotencyKey = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/pix';
    }

    public function defaultHeaders(): array
    {
        return $this->idempotencyHeaders($this->idempotencyKey);
    }

    public function defaultBody(): array
    {
        $payload = [
            'amount' => $this->amount,
            'description' => $this->description,
        ];

        $customer = $this->customer?->toArray() ?? [];

        if (! empty($customer)) {
            $payload['customer'] = $customer;
        }

        if (! empty($this->projectId)) {
            $payload['projectId'] = $this->projectId;
        }

        if (! empty($this->pspCredentialId)) {
            $payload['pspCredentialId'] = $this->pspCredentialId;
        }

        if (! empty($this->expiresIn)) {
            $payload['expiresIn'] = $this->expiresIn;
        }

        if (! empty($this->externalReference)) {
            $payload['externalReference'] = $this->externalReference;
        }

        if (! empty($this->metadata)) {
            $payload['metadata'] = $this->metadata;
        }

        if (! empty($this->split)) {
            $payload['split'] = array_map(
                fn (SplitAllocation $allocation) => $allocation->toArray(),
                array_values($this->split),
            );
        }

        return $payload;
    }
}
