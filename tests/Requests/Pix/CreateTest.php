<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Pix;

use LogicException;
use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Requests\Pix\Create;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    #[Test]
    public function it_requires_either_customer_or_customer_id(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Customer or CustomerId is required');

        new Create(
            amount: 100.0,
            description: 'Test Pix',
            customer: null,
            customerId: null,
            projectId: 'proj_123'
        );
    }

    #[Test]
    public function it_cannot_accept_both_customer_and_customer_id(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Use Customer OR CustomerId, not both at same time');

        new Create(
            amount: 100.0,
            description: 'Test Pix',
            customer: new Customer(name: 'John Doe', document: '11111'),
            customerId: 'cust_123'
        );
    }

    #[Test]
    public function it_sets_customer_correctly(): void
    {
        $customer = ['name' => 'Jane Doe', 'document' => '11111'];

        $request = new Create(
            amount: 200.0,
            description: 'Test Payment',
            customer: new Customer(
                name: $customer['name'],
                document: $customer['document'],
            )
        );

        $body = $request->defaultBody();

        $this->assertArrayHasKey('customer', $body);
        $this->assertSame($customer, $body['customer']);
        $this->assertArrayNotHasKey('customerId', $body);
        $this->assertSame(200.0, $body['amount']);
        $this->assertSame('Test Payment', $body['description']);
    }

    #[Test]
    public function it_sets_customer_id_correctly(): void
    {
        $request = new Create(
            amount: 150.0,
            description: 'Payment Pix',
            customerId: 'cust_456'
        );

        $body = $request->defaultBody();

        $this->assertArrayHasKey('customerId', $body);
        $this->assertSame('cust_456', $body['customerId']);
        $this->assertArrayNotHasKey('customer', $body);
    }
}
