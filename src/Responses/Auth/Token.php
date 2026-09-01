<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Auth;

use Saloon\Http\Response;

class Token extends Response
{
    public function getAccessToken(): ?string
    {
        return $this->json('access_token');
    }

    public function getTokenType(): ?string
    {
        return $this->json('token_type');
    }

    public function getExpiresIn(): ?int
    {
        $expiresIn = $this->json('expires_in');

        return $expiresIn === null ? null : (int) $expiresIn;
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
