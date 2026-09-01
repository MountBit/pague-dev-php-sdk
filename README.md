# MountBit Pague.dev PHP SDK

SDK em PHP para a **API pague.dev v2** — cobranças PIX, saques, projetos, subcontas, transações e webhooks, de forma tipada e testável.

Base URL: `https://api-gateway.pague.dev/v2` · Documentação oficial: [docs.pague.dev](https://docs.pague.dev)

---

## 📚 Índice

- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Início rápido](#-início-rápido)
- [Autenticação](#-autenticação)
- [Como o SDK é organizado](#-como-o-sdk-é-organizado)
- [Referência das rotas](#-referência-das-rotas)
  - [POST /auth — gerar access token](#-post-auth--gerar-access-token)
  - [GET /account — conta e saldo](#-get-account--conta-e-saldo)
  - [POST /pix — criar cobrança](#-post-pix--criar-cobrança)
  - [GET /transactions/{id} — consultar transação](#-get-transactionsid--consultar-transação)
  - [POST /transactions/{id}/refund — estornar](#-post-transactionsidrefund--estornar)
  - [POST /withdrawals — criar saque](#-post-withdrawals--criar-saque)
  - [POST /projects — criar projeto](#-post-projects--criar-projeto)
  - [GET /projects — listar projetos](#-get-projects--listar-projetos)
  - [GET /projects/{id} — buscar projeto](#-get-projectsid--buscar-projeto)
  - [POST /sub-accounts — criar subconta](#-post-sub-accounts--criar-subconta)
  - [GET /sub-accounts — listar subcontas](#-get-sub-accounts--listar-subcontas)
  - [GET /balance-blocks — bloqueios de saldo](#-get-balance-blocks--bloqueios-de-saldo)
- [Subcontas e split](#-subcontas-e-split)
- [Idempotência](#-idempotência)
- [Tratamento de erros](#-tratamento-de-erros)
- [Webhooks](#-webhooks)
- [Segurança](#-segurança)
- [Testes](#-testes)
- [Migração da v1](#-migração-da-v1)
- [Licença](#-licença)

---

## 🧾 Requisitos

- PHP **8.4+**
- Extensões: `ext-mbstring` (obrigatória via php-qrcode), `ext-gd` (opcional, só para gerar QR Code em imagem raster)
- Credenciais do painel [banking.pague.dev](https://banking.pague.dev)

---

## 📦 Instalação

```bash
composer require mountbit/pague-dev-php-sdk
```

---

## 🚀 Início rápido

Criar uma cobrança PIX e exibir o QR Code, do zero:

```php
use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;

$connector = new Api(
    clientId: getenv('PAGUEDEV_CLIENT_ID'),
    clientSecret: getenv('PAGUEDEV_CLIENT_SECRET'),
);

$response = $connector->send(new CreatePix(
    amount: 100.50,
    description: 'Pedido #12345',
    externalReference: 'pedido-12345',
));

if ($response->failed()) {
    // veja "Tratamento de erros"
}

echo $response->getPixCopyPaste();
echo '<img src="'.$response->getQrCodeBase64().'" alt="QR Code">';
```

Não é preciso gerenciar token: o SDK autentica sozinho na primeira chamada.

---

## 🔑 Autenticação

A v2 usa **client credentials** (OAuth2). No painel você gera um `client_id` e um `client_secret` — **o secret é exibido uma única vez**.

O ambiente é definido pelo prefixo da credencial, não pela URL:

| Prefixo | Ambiente |
| --- | --- |
| `mp_test_*` | Sandbox |
| `mp_live_*` | Produção |

```php
$connector = new Api(
    clientId: 'mp_live_xxx',
    clientSecret: 'seu_secret',
);
```

O SDK chama `POST /auth`, guarda o token em memória e o renova **30s antes** de expirar (o token vale 300s e não há refresh token). Você não precisa fazer nada.

### Reaproveitando o token entre requisições (Redis, etc.)

Em aplicações web cada requisição cria um connector novo, o que geraria um `POST /auth` por requisição. Para evitar, guarde o token no seu cache:

```php
$connector = new Api(
    clientId: getenv('PAGUEDEV_CLIENT_ID'),
    clientSecret: getenv('PAGUEDEV_CLIENT_SECRET'),
    accessToken: $cache->get('paguedev_token'),          // null na primeira vez
    accessTokenExpiresIn: $cache->ttl('paguedev_token'), // segundos restantes
);

$cache->put('paguedev_token', $connector->getAccessToken(), 270);
```

Se o token informado estiver expirado, o SDK obtém um novo automaticamente (desde que `clientId`/`clientSecret` tenham sido passados).

### Opções do connector

| Parâmetro | Padrão | Descrição |
| --- | --- | --- |
| `clientId` | `null` | Credencial do painel |
| `clientSecret` | `null` | Secret do painel |
| `baseUrl` | `https://api-gateway.pague.dev/v2` | Precisa ser HTTPS |
| `subAccount` | `null` | Envia `X-Sub-Account` em todas as rotas que aceitam |
| `accessToken` | `null` | Token pronto (cache externo) |
| `accessTokenExpiresIn` | `null` | Validade em segundos do token acima |
| `throwOnErrors` | `false` | `true` lança exceção tipada em qualquer resposta de erro |
| `userAgent` | `pague.dev - PHP SDK` | |
| `extraHeaders` | `[]` | Headers adicionais |
| `connectTimeout` | `5` | Segundos |
| `requestTimeout` | `10` | Segundos |

Informe **`clientId` + `clientSecret`**, ou um **`accessToken`**. Sem nenhum dos dois, o construtor lança `MissingCredentials`.

---

## 🧭 Como o SDK é organizado

Cada rota da API tem uma classe de **Request** (o que você envia) e uma de **Response** (o que você recebe). O padrão é sempre o mesmo:

```php
$request  = new Requests\<Recurso>\<Ação>(...);  // monta a chamada
$response = $connector->send($request);           // executa
$response->getAlgo();                             // lê campo a campo
$response->toArray();                             // ou o JSON inteiro
```

Toda Response tem `toArray()` (payload cru), `successful()`, `failed()` e `status()`.

Listas devolvem DTOs tipados (`Dtos\Project`, `Dtos\SubAccount`, `Dtos\BalanceBlock`), com autocomplete na IDE.

---

## 🧰 Referência das rotas

### 🔑 POST /auth — gerar access token

Normalmente **você não precisa chamar** esta rota: o connector já faz isso. Use apenas se quiser gerenciar o token por fora.

```php
use MountBit\PagueDev\Requests\Auth\Token;

$response = $connector->send(new Token('mp_live_xxx', 'seu_secret'));
```

**Parâmetros**

| Nome | Tipo | Obrigatório |
| --- | --- | --- |
| `clientId` | string | sim |
| `clientSecret` | string | sim |

**Resposta `200`**

```json
{ "access_token": "eyJhbGciOi...", "token_type": "Bearer", "expires_in": 300 }
```

| Getter | Retorno |
| --- | --- |
| `getAccessToken()` | `?string` |
| `getTokenType()` | `?string` — sempre `Bearer` |
| `getExpiresIn()` | `?int` — segundos |

**Erros:** `400` (credenciais ausentes), `401` (credencial inválida ou revogada).

---

### 🏦 GET /account — conta e saldo

```php
use MountBit\PagueDev\Requests\Account\Get as GetAccount;

$response = $connector->send(new GetAccount);

echo $response->getStatus();                          // approved, pending, suspended...
echo $response->getAvailableBalance()->amountFormatted; // 150.75 (reais)
echo $response->getAvailableBalance()->amount;          // 15075 (centavos)
```

Sem parâmetros.

**Resposta `200`**

```json
{
  "account": { "id": "uuid", "status": "approved" },
  "balance": {
    "available":    { "amount": 15075, "amountFormatted": 150.75 },
    "promotional":  { "amount": 0,     "amountFormatted": 0 },
    "held":         { "amount": 2500,  "amountFormatted": 25 },
    "total":        { "amount": 17575, "amountFormatted": 175.75 },
    "currency": "BRL",
    "updatedAt": "2026-02-10T14:30:00.000Z"
  }
}
```

| Getter | Retorno |
| --- | --- |
| `getId()` | `string` — id da conta |
| `getStatus()` | `string` — `approved`, `pending`, `pending_documents`, `pending_approval`, `rejected`, `suspended` |
| `getAvailableBalance()` | `BalanceAmount` — saldo disponível para saque |
| `getPromotionalBalance()` | `BalanceAmount` |
| `getHeldBalance()` | `BalanceAmount` — retido (MED, judicial) |
| `getTotalBalance()` | `BalanceAmount` |
| `getCurrency()` | `string` |
| `getBalanceUpdatedAt()` | `?string` |

`BalanceAmount` tem `->amount` (centavos, `int`) e `->amountFormatted` (reais, `float`).

**Erros:** `401`, `403`, `404`, `500`.

---

### 🎯 POST /pix — criar cobrança

```php
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;
use MountBit\PagueDev\Dtos\Pix\Customer;

$response = $connector->send(new CreatePix(
    amount: 100.50,
    description: 'Pagamento do pedido #12345',
    customer: new Customer(
        name: 'João da Silva',
        document: '12345678909',
        email: 'joao@example.com',
        phone: '+5511999998888',
    ),
    projectId: '3c90c3cc-0d44-4b50-8888-8dd25736052a',
    expiresIn: 3600,
    externalReference: 'pedido-12345',
    metadata: ['orderId' => '12345'],
    idempotencyKey: 'pedido-12345',
));
```

**Parâmetros**

| Nome | Tipo | Obrigatório | Regras |
| --- | --- | --- | --- |
| `amount` | float | **sim** | mínimo `1` (BRL) |
| `description` | string | **sim** | até 255 caracteres |
| `customer` | `Dtos\Pix\Customer` | não | todos os campos internos são opcionais |
| `projectId` | string (uuid) | não | sem ele, herda o projeto se a conta tiver só um |
| `pspCredentialId` | string (uuid) | não | fixa a instituição emissora; se ela estiver fora, a cobrança **falha** em vez de cair para outra |
| `expiresIn` | int | não | `300` a `604800` segundos (padrão `86400`) |
| `externalReference` | string | não | até 255; seu id para conciliação |
| `metadata` | array | não | pares chave-valor |
| `split` | `Dtos\Pix\SplitAllocation[]` | não | até 10 — ver [split](#-subcontas-e-split) |
| `idempotencyKey` | string | não | 8 a 255 — ver [idempotência](#-idempotência) |

**Resposta `201`**

```json
{
  "id": "3c90c3cc-0d44-4b50-8888-8dd25736052a",
  "status": "pending",
  "amount": 150.75,
  "currency": "BRL",
  "pixCopyPaste": "00020126580014br.gov.bcb.pix0136...",
  "qrCodeBase64": "data:image/png;base64,iVBORw0KGgo...",
  "pspPaymentId": "de3516a2-c63a-4c02-b132-a239bf42e183",
  "expiresAt": "2026-02-11T05:31:56Z",
  "externalReference": "pedido-12345",
  "createdAt": "2026-02-10T05:31:56Z"
}
```

| Getter | Retorno |
| --- | --- |
| `getId()` | `string` — id da cobrança **e** da transação |
| `getStatus()` | `string` — `pending`, `completed`, `failed`, `cancelled` |
| `getAmount()` | `float` |
| `getCurrency()` | `string` |
| `getPixCopyPaste()` | `string` — código copia e cola |
| `getQrCodeBase64()` | `?string` — PNG em data URI, pronto para `<img src>` |
| `getQrCode()` | `string` — SVG gerado localmente (ver abaixo) |
| `getPspPaymentId()` | `?string` — id no provedor, para conciliação |
| `getPspCredentialId()` | `?string` |
| `getExpiresAt()` | `string` |
| `getExternalReference()` | `?string` |
| `getCreatedAt()` | `string` |
| `getSplit()` | `SplitAllocation[]` |

**Erros:** `400` (validação), `401`, `404` (projeto/subconta inexistente), `500`.

#### QR Code

A API já devolve `qrCodeBase64` (PNG). Use-o quando bastar:

```php
echo '<img src="'.$response->getQrCodeBase64().'" alt="QR Code">';
```

Use `getQrCode()` quando precisar de **SVG** (escala sem perda), quiser controlar o nível de correção de erro, ou estiver montando o QR a partir de uma consulta a `GET /transactions/{id}` — que devolve o `pixCopyPaste` mas **não** devolve imagem:

```php
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;

echo '<img src="'.$response->getQrCode().'" alt="QR Code">';

// nível alto de correção — necessário para sobrepor um logo
echo $response->getQrCode(outputInterface: QRMarkupSVG::class, ecc: EccLevel::H);
```

Para gerar a partir de um copia e cola qualquer:

```php
use MountBit\PagueDev\Utils;

echo Utils::getInstance()->generateQrCode($transacao->getPixCopyPaste());
```

---

### 🧾 GET /transactions/{id} — consultar transação

O `id` aceita, nesta ordem: o **UUID** da transação, o seu **`externalReference`** ou a **`Idempotency-Key`** usada na criação.

```php
use MountBit\PagueDev\Requests\Transactions\GetById;

$response = $connector->send(new GetById(id: 'pedido-12345'));

if ($response->getStatus() === 'completed') {
    // libere o pedido
}
```

**Parâmetros**

| Nome | Tipo | Obrigatório |
| --- | --- | --- |
| `id` | string | **sim** |

**Resposta `200`** — principais getters:

| Getter | Retorno |
| --- | --- |
| `getId()` | `string` |
| `getStatus()` | `string` — `pending`, `completed`, `failed`, `cancelled` |
| `getType()` | `string` — `payment`, `fee`, `refund`, `chargeback`, `withdrawal`, `adjustment`, `referral_commission`, `internal_transfer` |
| `getPaymentMethod()` | `string` — `pix`, `credit_card`, `boleto` |
| `getAmount()` / `getCurrency()` | `float` / `string` |
| `getDescription()` | `?string` |
| `getExternalReference()` | `?string` |
| `getCustomerId()` / `getProjectId()` | `?string` |
| `getMetadata()` | `array` |
| `getPixCopyPaste()` | `?string` — só em PIX pendente |
| `getExpiresAt()` / `getPaidAt()` | `?string` |
| `getE2eId()` | `?string` — id fim-a-fim da rede PIX |
| `getCounterpartName()` | `?string` — nome do pagador, conforme o PSP |
| `getCounterpartDocument()` | `?string` — CPF/CNPJ sem máscara |
| `getCreatedAt()` / `getUpdatedAt()` | `string` / `?string` |

> **Não use polling para confirmar pagamento.** Consulte sob demanda e confie no webhook `payment_completed` para o fluxo automático.

**Erros:** `401`, `404`, `500`.

---

### ↩️ POST /transactions/{id}/refund — estornar

```php
use MountBit\PagueDev\Requests\Transactions\Refund;

$response = $connector->send(new Refund(
    id: '3c90c3cc-0d44-4b50-8888-8dd25736052a',
    reason: 'Cliente solicitou cancelamento',
));
```

**Parâmetros**

| Nome | Tipo | Obrigatório | Regras |
| --- | --- | --- | --- |
| `id` | string (uuid) | **sim** | transação de **pagamento** a estornar |
| `reason` | string | não | até 255 caracteres |

**Resposta `201`**

```json
{
  "originalTransactionId": "uuid",
  "pspProvider": "asaas",
  "pspRefundTransactionId": "ref_123",
  "status": "PENDING"
}
```

| Getter | Retorno |
| --- | --- |
| `getOriginalTransactionId()` | `string` |
| `getPspProvider()` | `string` |
| `getPspRefundTransactionId()` | `string` |
| `getStatus()` | `string` — `PENDING`, `CONFIRMED`, `ERROR` (maiúsculas, ao contrário das demais rotas) |

`PENDING` significa que o PSP aceitou; a confirmação chega pelo webhook `refund_completed`.

**Erros:** `400` (já estornada, saldo insuficiente), `401`, `404`, `500`.

---

### 💸 POST /withdrawals — criar saque

Informe **exatamente um** entre `amount` e `netAmount`:

- `amount` — valor **bruto** debitado do saldo; o destinatário recebe `amount - feeAmount`
- `netAmount` — valor **líquido** que o destinatário recebe; a API calcula o bruto e debita

```php
use MountBit\PagueDev\Requests\Withdrawals\Create as CreateWithdrawal;

$response = $connector->send(new CreateWithdrawal(
    pixKey: '12345678901',
    pixKeyType: 'cpf',
    holderName: 'João da Silva',
    holderDocument: '12345678901',
    holderDocumentType: 'cpf',
    amount: 150.75,
    externalReference: 'saque-001',
    idempotencyKey: 'saque-001',
));
```

**Parâmetros**

| Nome | Tipo | Obrigatório | Regras |
| --- | --- | --- | --- |
| `pixKey` | string | **sim** | chave do destinatário |
| `pixKeyType` | string | **sim** | `cpf`, `cnpj`, `email`, `phone`, `random` |
| `holderName` | string | **sim** | titular da conta |
| `holderDocument` | string | **sim** | CPF/CNPJ do titular |
| `holderDocumentType` | string | **sim** | `cpf`, `cnpj` |
| `amount` | float | um dos dois | mínimo `1` |
| `netAmount` | float | um dos dois | mínimo `1` |
| `projectId` | string (uuid) | não | |
| `externalReference` | string | não | até 255 |
| `idempotencyKey` | string | não | 8 a 255 — **use sempre aqui** |

**Resposta `201`**

```json
{
  "id": "uuid",
  "projectId": "uuid",
  "amount": 150.75,
  "feeAmount": 2.5,
  "netAmount": 148.25,
  "status": "pending",
  "snapshotPixKey": "12345678901",
  "snapshotPixKeyType": "cpf",
  "snapshotHolderName": "João da Silva",
  "snapshotHolderDocument": "12345678901",
  "failureReason": null,
  "pspReference": null,
  "createdAt": "2026-02-10T14:30:00.000Z",
  "processedAt": null,
  "externalReference": "saque-001"
}
```

| Getter | Retorno |
| --- | --- |
| `getId()` | `string` |
| `getStatus()` | `string` — `pending`, `processing`, `completed`, `failed` |
| `getAmount()` / `getFeeAmount()` / `getNetAmount()` | `float` |
| `getProjectId()` | `?string` |
| `getSnapshotPixKey()` / `getSnapshotPixKeyType()` | `?string` |
| `getSnapshotHolderName()` / `getSnapshotHolderDocument()` | `?string` |
| `getFailureReason()` | `?string` — preenchido quando `failed` |
| `getPspReference()` | `?string` |
| `getCreatedAt()` / `getProcessedAt()` | `?string` |
| `getExternalReference()` | `?string` |

> O status da criação **nunca é final**. O desfecho chega nos webhooks `withdrawal_completed`, `withdrawal_failed` ou `withdrawal_reversed`.

**Erros:** `400` (saldo insuficiente, limite, dados inválidos), `401`, `403` (permissão ou IP não autorizado), `404`, `500`.

---

### 📁 POST /projects — criar projeto

```php
use MountBit\PagueDev\Requests\Projects\Create as CreateProject;

$response = $connector->send(new CreateProject(
    name: 'Minha Loja',
    color: '#3B82F6',
    description: 'Loja virtual',
    logoUrl: 'https://example.com/logo.png',
));
```

| Nome | Tipo | Obrigatório | Regras |
| --- | --- | --- | --- |
| `name` | string | **sim** | até 255 |
| `color` | string | **sim** | hexadecimal, ex. `#3B82F6` |
| `description` | string | não | |
| `logoUrl` | string | não | URL |

**Resposta `201`:** `getId()`, `getName()`, `getColor()`, `getDescription()`, `getLogoUrl()`, `getCreatedAt()`.

**Erros:** `400`, `401`, `409` (projeto já existe), `500`.

---

### 📋 GET /projects — listar projetos

```php
use MountBit\PagueDev\Requests\Projects\GetList as ListProjects;

$response = $connector->send(new ListProjects(
    page: 1,
    limit: 20,
    sortBy: 'createdAt',
    sortOrder: 'desc',
));

foreach ($response->getItems() as $project) {
    echo $project->id.' '.$project->name;
}
```

| Nome | Tipo | Padrão | Valores |
| --- | --- | --- | --- |
| `page` | int | `1` | ≥ 1 |
| `limit` | int | `20` | 1 a 100 |
| `sortBy` | string | `createdAt` | `createdAt`, `updatedAt`, `name` |
| `sortOrder` | string | `desc` | `asc`, `desc` |

**Resposta `200`:** `getItems()` (array de `Dtos\Project`), `getTotal()`, `getPage()`, `getLimit()`, `getTotalPages()`.

`Dtos\Project` expõe `->id`, `->name`, `->color`, `->description`, `->logoUrl`, `->createdAt`, `->updatedAt`.

**Erros:** `401`, `500`.

---

### 🔍 GET /projects/{id} — buscar projeto

```php
use MountBit\PagueDev\Requests\Projects\GetById as GetProject;

$response = $connector->send(new GetProject(id: '3c90c3cc-...'));

$project = $response->getProject(); // Dtos\Project
```

Getters: `getId()`, `getName()`, `getColor()`, `getDescription()`, `getLogoUrl()`, `getCreatedAt()`, `getUpdatedAt()`, `getProject()`.

**Erros:** `401`, `404`, `500`.

---

### 👛 POST /sub-accounts — criar subconta

```php
use MountBit\PagueDev\Requests\SubAccounts\Create as CreateSubAccount;

$response = $connector->send(new CreateSubAccount(
    reference: 'loja-centro',
    name: 'Loja Centro',
));
```

| Nome | Tipo | Obrigatório | Regras |
| --- | --- | --- | --- |
| `reference` | string | **sim** | `^[a-z0-9][a-z0-9_-]{1,31}$` — 2 a 32 caracteres, **imutável**; `principal` é reservado |
| `name` | string | **sim** | até 255 |

**Resposta `201`:** `getId()`, `getReference()`, `getName()`, `getStatus()`, `getCreatedAt()`, `getSubAccount()`.

**Erros:** `400` (`SUB_ACCOUNT_INVALID_REFERENCE`), `401`, `403` (`SUB_ACCOUNT_FORBIDDEN`), `409` (`SUB_ACCOUNT_REFERENCE_TAKEN`, `SUB_ACCOUNT_QUOTA_EXCEEDED`), `500`.

---

### 📇 GET /sub-accounts — listar subcontas

```php
use MountBit\PagueDev\Requests\SubAccounts\GetList as ListSubAccounts;

foreach ($connector->send(new ListSubAccounts)->getData() as $subAccount) {
    echo $subAccount->reference.' '.$subAccount->status;
}
```

Sem parâmetros. **Resposta `200`:** `getData()` — array de `Dtos\SubAccount` (`->id`, `->reference`, `->name`, `->status`, `->createdAt`).

**Erros:** `401`, `403`, `500`.

---

### 🔒 GET /balance-blocks — bloqueios de saldo

Bloqueios MED, judiciais ou administrativos sobre o seu saldo.

```php
use MountBit\PagueDev\Requests\BalanceBlocks\GetList as ListBalanceBlocks;

$response = $connector->send(new ListBalanceBlocks(status: 'awaiting_response'));

foreach ($response->getItems() as $block) {
    echo $block->referenceNumber.' — '.$block->reason;
}
```

| Nome | Tipo | Obrigatório | Valores |
| --- | --- | --- | --- |
| `status` | string | não | `awaiting_response`, `defended`, `approved`, `rejected` — omitido retorna todos |

**Resposta `200`:** `getItems()` (array de `Dtos\BalanceBlock`), `getTotal()`.

`Dtos\BalanceBlock`: `->id`, `->transactionId`, `->amount`, `->status`, `->blockType` (`med`, `judicial`, `administrative`), `->referenceNumber`, `->reason`, `->externalReference`, `->e2eId`, `->resolutionReason`, `->resolvedAt`, `->createdAt`.

> `awaiting_response` tem **prazo de defesa**. Monitore esta rota ou trate o webhook `balance_block_created`.

**Erros:** `401`, `500`.

---

## 🪙 Subcontas e split

Subcontas são carteiras dentro da mesma conta. Para operar em nome de uma, use `forSubAccount()` — ele envia o header `X-Sub-Account` em todas as rotas que o aceitam (`/auth` e as próprias rotas de subconta não aceitam, e o SDK cuida disso):

```php
$lojaCentro = $connector->forSubAccount('loja-centro');

$lojaCentro->send(new CreatePix(amount: 50.00, description: 'Venda da loja centro'));
```

`forSubAccount()` devolve **um novo connector** e reaproveita o token já obtido — o original continua apontando para a conta principal.

Para repartir uma cobrança na liquidação:

```php
use MountBit\PagueDev\Dtos\Pix\SplitAllocation;

$connector->send(new CreatePix(
    amount: 100.00,
    description: 'Venda com rateio',
    split: [
        new SplitAllocation('loja-centro', 30.00),
        new SplitAllocation('loja-norte', 20.00),
    ],
));
```

Regras que valem a pena entender antes de usar:

- Quem cria a cobrança é o **originador**: recebe o valor bruto, paga a taxa cheia e **só então** reparte. A taxa não é rateada.
- A soma do split não pode ultrapassar o líquido (valor − taxa). Os valores são **fixos em reais**, não percentuais.
- O rateio ocorre **na liquidação**, não na criação.
- Estorno e MED debitam **apenas o originador** — quem recebeu rateio nunca é debitado.
- `principal` endereça a conta principal e só pode ser usado quando a cobrança parte de uma subconta.

---

## ♻️ Idempotência

`POST /pix` e `POST /withdrawals` aceitam `idempotencyKey` (8 a 255 caracteres). Reenviar a mesma chave devolve a **resposta original em cache** (TTL 24h) em vez de criar um novo registro — a proteção contra duplo clique, retry de fila e timeout de rede.

```php
new CreatePix(
    amount: 100.00,
    description: 'Pedido #12345',
    idempotencyKey: 'pedido-12345',  // use um id do seu domínio
);
```

Reusar a mesma chave com um **corpo diferente** retorna `422`.

Em saques isso é especialmente importante: sem a chave, um retry pode enviar dinheiro duas vezes.

---

## ⚠️ Tratamento de erros

Por padrão o SDK **não lança exceções** em respostas de erro — você inspeciona a resposta:

```php
$response = $connector->send($request);

if ($response->failed()) {
    $error = \MountBit\PagueDev\Exceptions\ApiException::fromResponse($response);

    logger()->error($error->getMessage(), ['traceId' => $error->getTraceId()]);
}
```

A exceção espelha o envelope de erro da API, campo a campo:

```json
{
  "statusCode": 409,
  "error": "Conflict",
  "message": "reference 'loja-centro' já usado nesta conta",
  "details": { "code": "SUB_ACCOUNT_REFERENCE_TAKEN" },
  "timestamp": "2026-09-01T19:26:01.916Z",
  "traceId": "be0a39e2dfb44b924445f04b0fce1928"
}
```

| Envelope | Acesso no SDK |
| --- | --- |
| `statusCode` | `$error->statusCode` |
| `error` | `$error->error` |
| `message` | `$error->getMessage()` — e `getMessages()` quando a API manda uma lista |
| `details` | `$error->getDetails()` — e `getErrorCode()` lê o `details.code` |
| `timestamp` | `$error->timestamp` |
| `traceId` | `$error->getTraceId()` — **informe no suporte** |
| envelope inteiro | `$error->toArray()` |

O `POST /auth` é a única rota com envelope reduzido (só `error` e `message`); nela `details`, `timestamp` e `traceId` vêm vazios.

### Exceções tipadas

Para lançar automaticamente, use `throwOnErrors: true` no connector; ou chame `$response->throw()` pontualmente:

```php
use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Exceptions\Conflict;
use MountBit\PagueDev\Exceptions\NotFound;

try {
    $response = $connector->send($request)->throw();
} catch (NotFound $e) {
    // 404
} catch (Conflict $e) {
    if ($e->hasErrorCode(ApiException::SUB_ACCOUNT_REFERENCE_TAKEN)) {
        // reference já usado
    }
} catch (ApiException $e) {
    // qualquer outro erro da API
}
```

| Exceção | Status |
| --- | --- |
| `BadRequest` | 400 |
| `Unauthorized` | 401 |
| `Forbidden` | 403 |
| `NotFound` | 404 |
| `Conflict` | 409 |
| `UnprocessableEntity` | 422 |
| `TooManyRequests` | 429 |
| `ServerError` | 5xx |
| `AuthenticationFailed` | falha ao obter o access token |

Todas estendem `ApiException` e carregam **a mensagem devolvida pelo pague.dev** — o SDK nunca reescreve nem inventa texto de erro. Se a API responder sem corpo, a mensagem fica vazia em vez de receber um texto fabricado: quem informa é o tipo da exceção e o `statusCode`.

Códigos de negócio conhecidos, disponíveis como constantes de `ApiException`: `SUB_ACCOUNT_NOT_FOUND`, `SUB_ACCOUNT_FORBIDDEN`, `SUB_ACCOUNT_SUSPENDED`, `SUB_ACCOUNT_REFERENCE_TAKEN`, `SUB_ACCOUNT_INVALID_REFERENCE`, `SUB_ACCOUNT_QUOTA_EXCEEDED`.

### Erros anteriores à chamada HTTP

Não têm mensagem da API para exibir, então o SDK também não inventa uma: quem comunica é o **tipo**.

| Exceção | Situação |
| --- | --- |
| `MissingCredentials` | credenciais ausentes ou incompletas ao construir o connector |
| `InvalidBaseUrl` | `baseUrl` sem HTTPS |
| `InvalidSignature` | assinatura do webhook não confere |
| `MissingWebhookSecret` | webhook secret não informado |
| `MalformedWebhookPayload` | corpo do webhook ilegível ou sem os campos obrigatórios |
| `InvalidWebhookEvent` | tipo de evento fora da lista aceita |
| `ExpiredWebhookTimestamp` | entrega fora da janela de tolerância |

As cinco últimas estendem `InvalidWebhook`, então `catch (InvalidWebhook $e)` captura qualquer falha de webhook.

---

## 🔔 Webhooks

A plataforma assina cada entrega com **HMAC-SHA256 do corpo bruto**, usando o seu webhook secret como chave, no header `X-Pague-Signature` com o prefixo `sha256=`.

```php
use MountBit\PagueDev\Utils;
use MountBit\PagueDev\Exceptions\InvalidWebhook;

try {
    $event = Utils::parseWebhook(
        rawBody: file_get_contents('php://input'),
        signatureHeader: $_SERVER['HTTP_X_PAGUE_SIGNATURE'] ?? '',
        webhookSecret: getenv('PAGUEDEV_WEBHOOK_SECRET'),
        shouldThrow: true,
        shouldValidateEventType: true,
        timestampHeader: $_SERVER['HTTP_X_PAGUE_TIMESTAMP'] ?? null,
        toleranceInSeconds: 300,
    );
} catch (InvalidWebhook $e) {
    http_response_code(400);
    exit;
}

http_response_code(200); // responda antes de processar

match ($event->event) {
    'payment_completed' => $this->confirmarPedido($event->data['externalReference']),
    'withdrawal_failed' => $this->reverterSaque($event->data['withdrawalId']),
    default => null,
};
```

Use o **corpo bruto** (`php://input`), nunca `$_POST` nem o JSON já decodificado: qualquer reserialização muda os bytes e invalida a assinatura.

| Parâmetro | Padrão | Descrição |
| --- | --- | --- |
| `rawBody` | — | corpo exato recebido |
| `signatureHeader` | — | `X-Pague-Signature` (com ou sem o prefixo `sha256=`) |
| `webhookSecret` | — | seu secret |
| `shouldThrow` | `false` | `false` devolve `null` em vez de lançar |
| `shouldValidateEventType` | `false` | rejeita eventos fora da lista |
| `validEventTypes` | `[]` | sua própria lista, se quiser restringir mais |
| `timestampHeader` | `null` | `X-Pague-Timestamp` |
| `toleranceInSeconds` | `null` | com o header acima, rejeita replays fora da janela |

O `WebhookEvent` devolvido expõe `->event`, `->eventId`, `->timestamp`, `->subAccount` e `->data`.

**Eventos:** `payment_completed`, `payment_expired`, `refund_completed`, `withdrawal_completed`, `withdrawal_failed`, `withdrawal_reversed`, `balance_block_created`, `balance_block_approved`, `balance_block_rejected`.

Boas práticas: responda `200` imediatamente e processe de forma assíncrona; **use o `eventId` para deduplicar** (a plataforma reenvia até 5 vezes com backoff); exponha o endpoint apenas por HTTPS.

---

## 🛡️ Segurança

O que o SDK já faz por você:

- **Não segue redirects** e exige verificação de certificado TLS. Um `Location` inesperado não redireciona sua requisição — nem o corpo dela, que em saques carrega CPF e chave PIX.
- **Exige HTTPS** na `baseUrl`, para nunca enviar credenciais em texto claro.
- **Não vaza credenciais em log**: `client_secret` e access token aparecem como `[redacted]` em `var_dump`, `print_r` e `serialize`, inclusive dentro de exceções.
- **Codifica identificadores** na URL, então um `externalReference` vindo de input do usuário não consegue reescrever a rota.
- **Valida a assinatura do webhook antes de fazer o parse** do JSON, em comparação de tempo constante.

Do seu lado:

- Guarde `client_secret` e webhook secret em variáveis de ambiente ou cofre — nunca no repositório.
- Use `mp_test_*` em desenvolvimento; `mp_live_*` movimenta dinheiro real.
- Sempre envie `idempotencyKey` em saques.
- Nunca registre o corpo completo de requisições de saque (contém PII).

---

## 🧪 Testes

```bash
composer test              # unitários (não fazem rede)
composer test-coverage     # com cobertura
composer test-integration  # chama o sandbox de verdade
composer format            # pint
```

Os testes de integração leem estas variáveis; sem as duas primeiras, são pulados:

```bash
PAGUEDEV_CLIENT_ID=mp_test_xxx   # use mp_test_* para não movimentar dinheiro real
PAGUEDEV_CLIENT_SECRET=xxx
PAGUEDEV_PROJECT_ID=             # opcional
PAGUEDEV_TRANSACTION_ID=         # opcional, habilita o teste de estorno
PAGUEDEV_SUB_ACCOUNT=            # opcional
PAGUEDEV_RUN_WITHDRAWALS=1       # opcional, saques movimentam saldo
```

### Testando a sua própria integração

O SDK usa [Saloon](https://docs.saloon.dev), então você pode simular respostas sem rede:

```php
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;

$mock = new MockClient([
    CreatePix::class => MockResponse::make([
        'id' => 'uuid',
        'status' => 'pending',
        'pixCopyPaste' => '000201...',
    ], 201),
]);

$connector = (new Api(accessToken: 'token'))->withMockClient($mock);
```

Passar `accessToken` evita que o mock precise responder também ao `POST /auth`.

---

## 🔄 Migração da v1

A v2 mudou host, autenticação e parte dos endpoints. `/charges`, `/customers`, `/metrics` e o PIX estático **não constam no contrato da v2**, e as classes correspondentes foram removidas do SDK.

Principais pontos ao migrar:

| v1 | v2 |
| --- | --- |
| `new Api($apiKey)` | `new Api(clientId: ..., clientSecret: ...)` |
| `https://api.pague.dev/v1` | `https://api-gateway.pague.dev/v2` |
| `Requests\Pix\Dynamic\Create` / `Pix\Static\Create` | `Requests\Pix\Create` |
| `Withdrawals\Create(bankAccountId: ...)` | campos PIX obrigatórios + `amount` **ou** `netAmount` |
| `Projects\GetList(search: ...)` | `sortBy` / `sortOrder` |

O `CHANGELOG.md` lista todos os breaking changes desta versão.

---

## 📄 Licença

MIT. Veja [LICENSE.md](LICENSE.md).
