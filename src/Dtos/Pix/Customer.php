<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos\Pix;

readonly class Customer
{
    public function __construct(
        public string $name,
        public string $document,
        public ?string $email = null,
        public ?string $phone = null,
    ) {}

    public function toArray()
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
