<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Customers;

use MountBit\PagueDev\Responses\Customers\Create as CreateResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Create extends Request implements HasBody
{
    use HasJsonBody;

    protected ?string $response = CreateResponse::class;

    public function __construct(
        protected readonly string $name,
        protected readonly string $document,
        protected readonly ?string $email = null,
        protected readonly ?string $phone = null
    ) {}

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/customers';
    }

    public function defaultBody(): array
    {
        $data = [
            'name' => $this->name,
            'document' => $this->document,
        ];

        if (! empty($this->email)) {
            $data['email'] = $this->email;
        }

        if (! empty($this->phone)) {
            $data['phone'] = $this->phone;
        }

        return $data;
    }
}
