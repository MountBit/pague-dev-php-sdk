# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

# pague-dev-php-sdk

PHP SDK for integrating with the pague.dev API, enabling creation of PIX payments, account management, client management, projects, charges, and transactions.

## Commands

### Tests
```bash
# Run all tests (unit + integration)
composer test

# Run tests with code coverage
composer test-coverage

# Run only integration tests
composer test-integration

# Run a specific test
vendor/bin/phpunit tests/Integration/Pix/Static/CreatePixTest.php

# Run tests in a specific folder
vendor/bin/phpunit tests/Integration/Pix/
```

### Formatting
```bash
# Format entire codebase
composer format

# Format only current file
vendor/bin/pint src/
```

---

## Code Architecture

### Project Structure

```
src/
├── Api.php                    # Main SDK - Saloon Connector extension
├── Utils.php                  # Utilities (QR codes, webhook validation)
├── Requests/                  # Requests to send to API
│   ├── Account/
│   │   └── GetList.php        # Get account info
│   ├── Pix/
│   │   ├── Static/Create.php  # Static PIX
│   │   └── Dynamic/Create.php # Dynamic PIX
│   ├── Charges/Create.php
│   ├── Charges/GetById.php
│   ├── Charges/GetList.php
│   ├── Customers/Create.php
│   ├── Customers/GetList.php
│   ├── Projects/Create.php
│   ├── Projects/GetList.php
│   ├── Transactions/GetById.php
│   ├── Withdrawals/Create.php
│   └── Metrics/GetList.php
├── Responses/                 # Response types
│   ├── Account/
│   │   └── GetList.php        # Account info response
│   ├── Pix/
│   │   ├── Static/Create.php
│   │   └── Dynamic/Create.php
│   ├── Charges/Create.php
│   ├── Charges/GetById.php
│   ├── Charges/GetList.php
│   ├── Customers/Create.php
│   ├── Customers/GetList.php
│   ├── Projects/Create.php
│   ├── Projects/GetList.php
│   ├── Transactions/GetById.php
│   ├── Withdrawals/Create.php
│   └── Metrics/GetList.php
├── Dtos/                      # Data Transfer Objects
│   ├── Customer.php           # Inline customer object for PIX
│   ├── Charge.php
│   ├── Project.php
│   └── WebhookEvent.php
└── Exceptions/
    ├── InvalidSignature.php   # Exception for invalid signature
    └── InvalidWebhook.php     # Exception for invalid webhook
```

### Naming Conventions

**Requests**: Classes extending `Saloon\Http\Request`
- Ending: `Create`, `GetById`, `GetList`
- Explicit response mapping via `$response` property

**Responses**: Classes extending `Saloon\Http\Response`
- Public methods return specific data (e.g., `getAmount()`, `getQrCode()`)
- Support for `toArray()` method to get complete JSON

**Dtos**: Objects for complex data transmission
- Used inline in requests
- Not stored

### PIX Architecture

**Static PIX**:
- Endpoint: `/pix/qrcode-static`
- Request: `MountBit\PagueDev\Requests\Pix\Static\Create`
- Response contains: `qrCodeBase64`, `pixCopyPaste`, `getQrCode()` (generated SVG)

**Dynamic PIX**:
- Endpoint: `/pix/qrcode-dynamic`
- Request: `MountBit\PagueDev\Requests\Pix\Dynamic\Create`
- Can receive inline `customer` or `customerId`
- Same response type with equivalent methods

**QR Code Generation Flow**:
1. Create PIX request (static or dynamic)
2. Send via `$connector->send($request)`
3. Response contains `pixCopyPaste` (VPA string)
4. Call `$response->getQrCode()` to generate SVG dynamically
5. Method uses `chillerlan\QRCode` to render

### Get Account

Retrieves account information including balance and company details:

```php
use MountBit\PagueDev\Requests\Account\GetList as GetAccountList;

$request = new GetAccountList();

$response = $connector->send($request);

echo $response->getCompanyName();
echo $response->getBalanceTotalAmountFormatted();
```

**Response getters**:
- `getCompanyName()` - Company legal name
- `getCompanyTradeName()` - Trade name
- `getCompanyCnpj()` - CNPJ
- `getCompanyEmail()` - Company email
- `getCompanyPhone()` - Company phone
- `getCompanyStatus()` - Account status
- `getBalanceAvailableAmount()` / `getBalanceAvailableAmountFormatted()` - Available balance
- `getBalancePromotionalAmount()` / `getBalancePromotionalAmountFormatted()` - Promotional balance
- `getBalanceHeldAmount()` / `getBalanceHeldAmountFormatted()` - Held balance
- `getBalanceTotalAmount()` / `getBalanceTotalAmountFormatted()` - Total balance
- `getBalanceCurrency()` - Currency code
- `getBalanceUpdatedAt()` - Last balance update

### Webhook Parsing

The SDK includes complete webhook validation:

```php
$event = Utils::parseWebhook(
    $rawBody,
    $_SERVER['HTTP_X_SIGNATURE'],
    $webhookSecret,
    shouldThrow: true,        // throws exception if invalid
    shouldValidateEventType: true
);
```

**Validations**:
- Verifies HMAC-SHA256 signature
- Decodes JSON
- Optional: validates event type (default: `payment_completed`, `refund_completed`, `withdrawal_completed`, `withdrawal_failed`)

**Exceptions**:
- `InvalidSignature`: HMAC signature does not match
- `InvalidWebhook`: Invalid JSON or unrecognized event

---

## Key Parts

### Authentication

- Use `MountBit\PagueDev\Api` with `apiKey` in constructor
- Authentication via `X-API-Key` header
- Base URL: `https://api.pague.dev/v1` (customizable via `$baseUrl`)
- Default headers: `Accept: application/json`, `Content-Type: application/json`

### Environment Requirements

- PHP 8.4+
- Suggested extensions: `gd` (QR codes), `mbstring`
- Composer

### Main Libraries

- `saloonphp/saloon`: HTTP client with async and mocking support
- `chillerlan/php-qrcode`: QR code generation in SVG
- `phpunit/phpunit`: Testing

---

## Tests

### Structure

```
tests/
├── TestCase.php                    # Base for unit tests (mocked)
├── ApiTest.php                     # SDK main tests
├── UtilsTest.php                   # Webhook validation tests
├── Integration/                     # Tests against real API
│   ├── Charges/
│   ├── Customers/
│   ├── Projects/
│   ├── Pix/
│   ├── Transactions/
│   └── Withdrawals/
├── Requests/                        # Request validation tests
└── fixtures/                        # Mocked JSON responses
```

### Test Patterns

**Unit tests (mocked)**:
- Use `Saloon\MockClient` to avoid real requests
- JSON fixtures in `tests/fixtures/`
- Executed via `composer test`

**Integration tests**:
- Labeled with `@group integration`
- Executed separately via `composer test-integration`
- Require valid API key

**Request test pattern**:
```php
class MyRequestTest extends TestCase
{
    public function testCreatesValidPayload(): void
    {
        $request = new MyRequest(
            amount: 100.00,
            description: 'Test'
        );
        
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('json')->willReturn(
            $this->fixture('/responses/expected.json')
        );
        
        $this->connector->client->getConnector()->setMockResponses([$mockResponse]);
        
        $response = $this->connector->send($request);
        $this->assertEquals('expected_id', $response->getId());
    }
}
```

### Run Single Test

```bash
# Specific test
vendor/bin/phpunit tests/Requests/Pix/Static/CreatePixRequestTest.php --filter=testCreatesValidPayload

# Tests in folder
vendor/bin/phpunit tests/Requests/Pix/ --filter=Static

# Verbose output
vendor/bin/phpunit --testdox
```

---

## Code Patterns

### Creating New Request

1. Extend `Saloon\Http\Request`
2. Implement `resolveEndpoint()` and `defaultBody()`
3. Map response via `$response` property (optional, if different)
4. Namespace names: `MountBit\PagueDev\Requests\{Resource}\{Operation}`

### Creating New Response

1. Extend `Saloon\Http\Response`
2. Implement public getter methods for fields (e.g., `getId()`, `getAmount()`)
3. Implement `toArray()` for complete JSON return
4. For complex fields, return `?Type` (nullable)
5. Namespace names: `MountBit\PagueDev\Responses\{Resource}\{Operation}`

### Get Account

Retrieves account information including balance and company details:

```php
use MountBit\PagueDev\Requests\Account\GetList as GetAccountList;

$request = new GetAccountList();

$response = $connector->send($request);

echo $response->getCompanyName();
echo $response->getBalanceTotalAmountFormatted();
```

**Response getters**:
- `getCompanyName()` - Company legal name
- `getCompanyTradeName()` - Trade name
- `getCompanyCnpj()` - CNPJ
- `getCompanyEmail()` - Company email
- `getCompanyPhone()` - Company phone
- `getCompanyStatus()` - Account status
- `getBalanceAvailableAmount()` / `getBalanceAvailableAmountFormatted()` - Available balance
- `getBalancePromotionalAmount()` / `getBalancePromotionalAmountFormatted()` - Promotional balance
- `getBalanceHeldAmount()` / `getBalanceHeldAmountFormatted()` - Held balance
- `getBalanceTotalAmount()` / `getBalanceTotalAmountFormatted()` - Total balance
- `getBalanceCurrency()` - Currency code
- `getBalanceUpdatedAt()` - Last balance update

### QR Code Generation

```php
$response = $connector->send($request);
$qrCodeSvg = $response->getQrCode(
    imageType: QROutputInterface::MARKUP_SVG,
    ecc: EccLevel::M  // Error correction: L, M, Q, H
);
```

To customize:
- Use the `getQrCode()` method in responses
- Or call `Utils::getInstance()->generateQrCode()` directly
