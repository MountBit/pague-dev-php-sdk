<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos\Pix;

readonly class Customer
{
    public function __construct(
        public ?string $name = null,
        public ?string $document = null,
        public ?string $email = null,
        public ?string $phone = null,
    ) {}

    public function toArray(): array
    {
        $data = [];

        if (! empty($this->name)) {
            $data['name'] = $this->name;
        }

        if (! empty($this->document)) {
            $data['document'] = $this->document;
        }

        if (! empty($this->email)) {
            $data['email'] = $this->email;
        }

        if (! empty($this->phone)) {
            $data['phone'] = $this->phone;
        }

        return $data;
    }
}
