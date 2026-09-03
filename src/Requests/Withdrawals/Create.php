<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Withdrawals;

use MountBit\PagueDev\Concerns\HasIdempotencyKey;
use MountBit\PagueDev\Responses\Withdrawals\Create as CreateResponse;
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

    public function __construct(
        public readonly string $pixKey,
        public readonly string $pixKeyType,
        public readonly string $holderName,
        public readonly string $holderDocument,
        public readonly string $holderDocumentType,
        public readonly ?float $amount = null,
        public readonly ?float $netAmount = null,
        public readonly ?string $projectId = null,
        public readonly ?string $externalReference = null,
        public readonly ?string $idempotencyKey = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/withdrawals';
    }

    public function defaultHeaders(): array
    {
        return $this->idempotencyHeaders($this->idempotencyKey);
    }

    public function defaultBody(): array
    {
        $data = [
            'pixKey' => $this->pixKey,
            'pixKeyType' => $this->pixKeyType,
            'holderName' => $this->holderName,
            'holderDocument' => $this->holderDocument,
            'holderDocumentType' => $this->holderDocumentType,
        ];

        if ($this->amount !== null) {
            $data['amount'] = $this->amount;
        }

        if ($this->netAmount !== null) {
            $data['netAmount'] = $this->netAmount;
        }

        if (! empty($this->projectId)) {
            $data['projectId'] = $this->projectId;
        }

        if (! empty($this->externalReference)) {
            $data['externalReference'] = $this->externalReference;
        }

        return $data;
    }
}
