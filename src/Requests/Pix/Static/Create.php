<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Pix\Static;

use MountBit\PagueDev\Responses\Pix\Static\Create as CreateResponse;
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
        protected readonly ?string $projectId = null,
        protected readonly ?string $externalReference = null,
        protected readonly ?array $metadata = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/pix/qrcode-static';
    }

    public function defaultBody(): array
    {
        $payload = [
            'amount' => $this->amount,
            'description' => $this->description,
            'projectId' => $this->projectId,
        ];

        if (! empty($this->externalReference)) {
            $payload['externalReference'] = $this->externalReference;
        }

        if (! empty($this->metadata)) {
            $payload['metadata'] = $this->metadata;
        }

        if (! empty($this->projectId)) {
            $payload['projectId'] = $this->projectId;
        }

        return $payload;
    }
}
