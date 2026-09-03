<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Pix;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use MountBit\PagueDev\Dtos\Pix\SplitAllocation;
use MountBit\PagueDev\Utils;
use Saloon\Http\Response;

class Create extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getAmount(): float
    {
        return (float) $this->json('amount');
    }

    public function getCurrency(): string
    {
        return $this->json('currency');
    }

    public function getPixCopyPaste(): string
    {
        return $this->json('pixCopyPaste');
    }

    public function getQrCodeBase64(): ?string
    {
        return $this->json('qrCodeBase64');
    }

    public function getPspPaymentId(): ?string
    {
        return $this->json('pspPaymentId');
    }

    public function getPspCredentialId(): ?string
    {
        return $this->json('pspCredentialId');
    }

    public function getExpiresAt(): string
    {
        return $this->json('expiresAt');
    }

    public function getExternalReference(): ?string
    {
        return $this->json('externalReference');
    }

    public function getCreatedAt(): string
    {
        return $this->json('createdAt');
    }

    /**
     * @return array<SplitAllocation>
     */
    public function getSplit(): array
    {
        return array_map(
            fn (array $allocation) => SplitAllocation::fromArray($allocation),
            $this->json('split') ?: []
        );
    }

    public function getQrCode(
        string $outputInterface = QRMarkupSVG::class,
        int $ecc = EccLevel::M,
    ): string {
        return Utils::getInstance()->generateQrCode(
            data: $this->getPixCopyPaste(),
            outputInterface: $outputInterface,
            ecc: $ecc,
        );
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
