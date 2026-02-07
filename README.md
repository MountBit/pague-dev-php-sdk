# MountBit Pague.dev PHP SDK

SDK em PHP para integrar facilmente com a **API pague.dev**, permitindo que aplicações PHP criem cobranças PIX, gerenciem clientes, projetos, cobranças (links de pagamento) e transações de forma simples e tipada.

A API do pague.dev oferece recursos como PIX, cobranças, clientes e transações por meio de uma interface REST limpa, com autenticação via API Key e respostas em JSON. ([docs.pague.dev](https://docs.pague.dev/?utm_source=chatgpt.com))

---

## 📦 Instalação

Instale via **Composer**:

```bash
composer require mountbit/pague-dev-php-sdk
```

---

## 🚀 Começando

### 1. Configurar o Cliente

```php
use MountBit\PagueDev\Api;

$apiKey = 'pd_test_xxx';
$connector = new Api($apiKey);
```

---

## 🧰 Recursos Disponíveis

### 🎯 PIX

#### Criar cobrança PIX com objeto Customer

```php
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;
use MountBit\PagueDev\Dtos\Pix\Customer;

$request = new CreatePix(
    amount: 100.50,
    description: 'Pagamento PIX',
    projectId: 'proj_123',
    customer: new Customer(
        name: 'John Doe',
        document: '12345678910',
        email: 'email@example.com',
        phone: '12345',
    ),
    expiresIn: 3600,
    externalReference: 'ref_001',
    metadata: ['orderId' => 'order_001'],
);

$response = $connector->send($request);

print_r($response->json());
```

---

#### Criar cobrança PIX com Customer Id

```php
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;
use MountBit\PagueDev\Dtos\Pix\Customer;

$request = new CreatePix(
    amount: 100.50,
    description: 'Pagamento PIX',
    projectId: 'proj_123',
    customerId: 'customer_123',
    expiresIn: 3600,
    externalReference: 'ref_001',
    metadata: ['orderId' => 'order_001'],
);

$response = $connector->send($request);

print_r($response->json());
```

---

#### Gerar QR Code a partir de cobrança PIX

```php
use MountBit\PagueDev\Requests\Pix\Create as CreatePixRequest;
use MountBit\PagueDev\Responses\Pix\Create as CreatePixResponse;

$request = new CreatePixRequest(
    ...dados
);

/** @var CreatePixResponse $response **/
$response = $connector->send($request);

echo '<img src="'.$response->getQrCode().'" alt="QR Code" />';
```

---

### 💳 Cobranças (Links de pagamento)

#### Criar cobrança

```php
use MountBit\PagueDev\Requests\Charges\Create as CreateCharge;

$request = new CreateCharge(
    projectId: 'proj_123',
    name: 'Cobrança teste',
    amount: 150.0,
    paymentMethods: ['pix'],
    customerId: null,
    allowCoupons: true
);

$response = $connector->send($request);

print_r($response->json());
```

#### Buscar cobrança por ID

```php
use MountBit\PagueDev\Requests\Charges\GetById;

$request = new GetById(id: 'charge_123');

$response = $connector->send($request);

print_r($response->json());
```

#### Listar cobranças

```php
use MountBit\PagueDev\Requests\Charges\GetList;

$request = new GetList(page: 1, limit: 10, search: 'teste');

$response = $connector->send($request);

print_r($response->json());
```

---

### 👤 Clientes

#### Criar cliente

```php
use MountBit\PagueDev\Requests\Customers\Create as CreateCustomer;

$request = new CreateCustomer(
    name: 'Maria Silva',
    document: '12345678900',
    email: 'maria@example.com',
    phone: '+551199999999'
);

$response = $connector->send($request);

print_r($response->json());
```

#### Listar clientes

```php
use MountBit\PagueDev\Requests\Customers\GetList as GetCustomersList;

$request = new GetCustomersList(page: 1, limit: 10, search: 'Maria');

$response = $connector->send($request);

print_r($response->json());
```

---

### 🗂️ Projetos

#### Criar projeto

```php
use MountBit\PagueDev\Requests\Projects\Create as CreateProject;

$request = new CreateProject(
    name: 'Projeto Teste',
    color: '#FF0000',
    description: 'Projeto de exemplo',
    logoUrl: 'https://example.com/logo.png'
);

$response = $connector->send($request);

print_r($response->json());
```

#### Listar projetos

```php
use MountBit\PagueDev\Requests\Projects\GetList as GetProjectsList;

$request = new GetProjectsList(page: 1, limit: 10, search: 'Teste');

$response = $connector->send($request);

print_r($response->json());
```

---

### 📊 Transações

#### Buscar transação por ID

```php
use MountBit\PagueDev\Requests\Transactions\GetById as GetTransactionById;

$request = new GetTransactionById(id: 'txn_123');

$response = $connector->send($request);

print_r($response->json());
```

---

### 🔔 Webhooks

O SDK fornece utilitários para **verificar e parsear eventos de webhook** com segurança.

#### Estrutura de um WebhookEvent

```php
use MountBit\PagueDev\Dtos\WebhookEvent;

$event = new WebhookEvent(
    event: 'payment_completed',
    eventId: 'a0b78f10-c7f4-4f5d-98dd-3e36eafeb812',
    timestamp: '2026-01-11T19:03:28.280Z',
    data: [
        'amount' => 150.0,
        'customerId' => 'cust_123'
    ]
);
```

#### Parsear Webhook recebido

```php
use MountBit\PagueDev\Utils;
use MountBit\PagueDev\Exceptions\InvalidSignature;
use MountBit\PagueDev\Exceptions\InvalidWebhook;

$rawBody = file_get_contents('php://input'); // corpo completo em formato puro
$signatureHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$webhookSecret = 'sua_chave_secreta';

try {
    $webhookEvent = Utils::getInstance()->parseWebhook(
        $rawBody,
        $signatureHeader,
        $webhookSecret,
        shouldThrow: true,            // lança exceção se inválido
        shouldValidateEventType: true // valida tipos de evento
    );

    // Processar o evento
    if ($webhookEvent->event === 'payment_completed') {
        // lógica para pagamento concluído
    }

} catch (InvalidSignature $e) {
    echo 'Assinatura inválida';
} catch (InvalidWebhook $e) {
    echo 'Webhook inválido';
}
```

---

## 🧪 Testes

Use **Saloon MockClient** e fixtures JSON para validar requisições e respostas:

```bash
composer test
```

---

## 📄 Documentação da API

Detalhes completos dos endpoints, parâmetros e respostas estão disponíveis em:
[https://docs.pague.dev](https://docs.pague.dev)

---

## 🛡️ Autenticação

Todas as chamadas exigem uma **API Key** no header:

```
X-API-Key: sua_api_key
```

---

## 📦 Requisitos

- PHP 8.4+
- Composer

---

## 👥 Contribuições

Contribuições são bem-vindas! Para adicionar novas features ou corrigir bugs:

1. Faça um fork do repositório
2. Crie um branch para sua feature e/ou correção
3. Abra um Pull Request

---

## 📝 Licença

Este projeto está licenciado sob a licença **MIT**.
