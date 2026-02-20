<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Withdrawals;

use LogicException;
use MountBit\PagueDev\Responses\Withdrawals\Create as CreateResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Create extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected ?string $response = CreateResponse::class;

    private array $pixFields = [
        'pixKey',
        'pixKeyType',
        'holderName',
        'holderDocument',
        'holderDocumentType',
    ];

    public function __construct(
        public readonly float $amount,
        public readonly ?string $bankAccountId = null,
        public readonly ?string $pixKey = null,
        public readonly ?string $pixKeyType = null,
        public readonly ?string $holderName = null,
        public readonly ?string $holderDocument = null,
        public readonly ?string $holderDocumentType = null,
    ) {
        $pixValues = [
            $this->pixKey,
            $this->pixKeyType,
            $this->holderName,
            $this->holderDocument,
            $this->holderDocumentType,
        ];

        $filledPixValues = array_filter($pixValues, fn ($v) => ! empty($v));

        $hasAnyPix = count($filledPixValues) > 0;
        $hasAllPix = count($filledPixValues) === count($pixValues);

        if (! empty($this->bankAccountId) && $hasAnyPix) {
            throw new LogicException(
                'Use bankAccountId OR Pix fields, not both at the same time'
            );
        }

        if (empty($this->bankAccountId) && $hasAnyPix && ! $hasAllPix) {
            throw new LogicException(
                'When using Pix fields, all of them are required'
            );
        }

        if (empty($this->bankAccountId) && ! $hasAnyPix) {
            throw new LogicException(
                'bankAccountId or Pix fields are required'
            );
        }
    }

    public function resolveEndpoint(): string
    {
        return '/withdrawals';
    }

    public function defaultBody(): array
    {
        $data = [
            'amount' => $this->amount,
        ];

        if (! empty($this->bankAccountId)) {
            $data['bankAccountId'] = $this->bankAccountId;
        } else {
            $data['pixKey'] = $this->pixKey;
            $data['pixKeyType'] = $this->pixKeyType;
            $data['holderName'] = $this->holderName;
            $data['holderDocument'] = $this->holderDocument;
            $data['holderDocumentType'] = $this->holderDocumentType;
        }

        return $data;
    }
}
