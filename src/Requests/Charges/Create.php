<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Charges;

use MountBit\PagueDev\Responses\Charges\Create as CreateResponse;
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
        public readonly string $projectId,
        public readonly string $name,
        public readonly float $amount,
        public readonly array $paymentMethods,
        public readonly ?string $customerId = null,
        public readonly ?bool $allowCoupons = null,
        public readonly ?array $notifications = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/charges';
    }

    public function defaultBody(): array
    {
        $data = [
            'projectId' => $this->projectId,
            'name' => $this->name,
            'amount' => $this->amount,
            'paymentMethods' => $this->paymentMethods,
        ];

        if (! empty($this->customerId)) {
            $data['customerId'] = $this->customerId;
        }

        if (! empty($this->allowCoupons)) {
            $data['allowCoupons'] = $this->allowCoupons;
        }

        if (! empty($this->notifications)) {
            $data['notifications'] = $this->notifications;
        }

        return $data;
    }
}
