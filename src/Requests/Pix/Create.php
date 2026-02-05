<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Pix;

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
        protected readonly string $projectId,
        protected readonly array $customer,
        protected readonly ?int $expiresIn = null,
        protected readonly ?string $externalReference = null,
        protected readonly ?array $metadata = null,
    ) {}

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
            'customer' => $this->customer,
        ];

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
