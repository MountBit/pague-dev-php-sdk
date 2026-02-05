<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Projects;

use MountBit\PagueDev\Responses\Projects\Create as CreateResponse;
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
        protected readonly string $name,
        protected readonly ?string $color = null,
        protected readonly ?string $description = null,
        protected readonly ?string $logoUrl = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/projects';
    }

    public function defaultBody(): array
    {
        $data = [
            'name' => $this->name,
        ];

        if (! empty($this->color)) {
            $data['color'] = $this->color;
        }

        if (! empty($this->description)) {
            $data['description'] = $this->description;
        }

        if (! empty($this->logoUrl)) {
            $data['logoUrl'] = $this->logoUrl;
        }

        return $data;
    }
}
