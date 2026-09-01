<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration;

use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Exceptions\Conflict;
use MountBit\PagueDev\Exceptions\NotFound;
use MountBit\PagueDev\Requests\Projects\GetById;
use MountBit\PagueDev\Requests\SubAccounts\Create as CreateSubAccount;
use MountBit\PagueDev\Requests\SubAccounts\GetList as GetSubAccounts;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class ExceptionsTest extends ApiTestCase
{
    private const string MISSING_UUID = '00000000-0000-4000-8000-000000000000';

    #[Test]
    public function it_maps_a_missing_project_to_a_not_found_exception(): void
    {
        $response = $this->api->send(new GetById(id: self::MISSING_UUID));

        $this->assertSame(404, $response->status());

        $exception = ApiException::fromResponse($response);

        $this->assertInstanceOf(NotFound::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
        $this->assertNotEmpty($exception->getTraceId());
        $this->assertSame('Project', $exception->getDetails()['resource'] ?? null);
    }

    #[Test]
    public function it_maps_a_duplicated_sub_account_reference_to_a_conflict_exception(): void
    {
        $existing = $this->api->send(new GetSubAccounts)->getData();

        if (empty($existing)) {
            $this->markTestSkipped('The sandbox account has no sub accounts.');
        }

        $response = $this->api->send(
            new CreateSubAccount(
                reference: $existing[0]->reference,
                name: 'Duplicated reference',
            )
        );

        $this->assertSame(409, $response->status());

        $exception = ApiException::fromResponse($response);

        $this->assertInstanceOf(Conflict::class, $exception);
        $this->assertTrue(
            $exception->hasErrorCode(ApiException::SUB_ACCOUNT_REFERENCE_TAKEN)
        );
        $this->assertNotEmpty($exception->getTraceId());
    }

    #[Test]
    public function it_throws_the_mapped_exception_when_the_connector_is_configured_to(): void
    {
        $api = $this->api->forSubAccount(null);

        $response = $api->send(new GetById(id: self::MISSING_UUID));

        $this->expectException(NotFound::class);

        $response->throw();
    }
}
