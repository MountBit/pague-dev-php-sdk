<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Pix;

use LogicException;
use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Responses\Pix\Create as CreateResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Create extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected ?string $response = CreateResponse::class;

    public function __construct(
        protected readonly float $amount,
        protected readonly string $description,
        protected readonly ?Customer $customer = null,
        protected readonly ?string $customerId = null,
        protected readonly ?string $projectId = null,
        protected readonly ?int $expiresIn = null,
        protected readonly ?string $externalReference = null,
        protected readonly ?array $metadata = null,
    ) {
        if (empty($customer) && empty($customerId)) {
            throw new LogicException('Customer or CustomerId is required');
        }

        if (! empty($customer) && ! empty($customerId)) {
            throw new LogicException('Use Customer OR CustomerId, not both at same time');
        }
    }

    public function resolveEndpoint(): string
    {
        return '/pix';
    }

    public function defaultBody(): array
    {
        $payload = [
            'amount' => $this->amount,
            'description' => $this->description,
            'projectId' => $this->projectId,
        ];

        if (! empty($this->customer)) {
            $payload['customer'] = $this->customer->toArray();
        }

        if (! empty($this->customerId)) {
            $payload['customerId'] = $this->customerId;
        }

        if (! empty($this->projectId)) {
            $payload['projectId'] = $this->projectId;
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

        return $payload;
    }
}
