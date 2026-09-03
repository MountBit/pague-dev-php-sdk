# Changelog

All notable changes to `pague-dev-php-sdk` will be documented in this file.

## v3.0.0 - 2026-09-01

Migração completa para a **API pague.dev v2**. Mudam o host, o esquema de autenticação e parte dos endpoints, então esta versão **não é compatível** com a v2.x do SDK.

As 12 rotas da v2 foram validadas em caminho de sucesso contra o sandbox real, não apenas por fixture.

### ⚠️ Breaking changes

* `new Api($apiKey)` deu lugar a `new Api(clientId: ..., clientSecret: ...)`. O SDK obtém o access token no `POST /auth`, guarda em memória e renova 30s antes de expirar
* Base URL passou de `https://api.pague.dev/v1` para `https://api-gateway.pague.dev/v2`
* Removidas as classes de `/charges`, `/customers`, `/metrics` e do PIX estático — não constam no contrato da v2
* `Requests\Pix\Dynamic\Create` passou a ser `Requests\Pix\Create`; os namespaces `Dynamic` e `Static` deixaram de existir
* `Withdrawals\Create`: `bankAccountId` foi removido e os campos PIX passaram a vir primeiro na assinatura; informe **exatamente um** entre `amount` e `netAmount`
* `Projects\GetList`: o parâmetro `search` deu lugar a `sortBy` e `sortOrder` (a v2 não tem busca textual)
* `Projects\Create`: `color` passou a ser obrigatório
* `generateQrCode()` e `getQrCode()`: o segundo argumento passou de um formato em string para o FQCN da classe de saída do php-qrcode v6, e chama-se `outputInterface` (padrão: `QRMarkupSVG::class`)
* O SDK deixou de seguir redirects — um `3xx` inesperado chega ao integrador em vez de ser seguido às cegas
* Webhooks: a assinatura passou a ser calculada corretamente (ver Segurança). Quem tiver contornado a falha reimplementando o algoritmo antigo precisa remover o contorno
* Variáveis dos testes de integração perderam o infixo `SANDBOX`: agora `PAGUEDEV_CLIENT_ID`, `PAGUEDEV_CLIENT_SECRET`, `PAGUEDEV_PROJECT_ID`, `PAGUEDEV_TRANSACTION_ID`, `PAGUEDEV_SUB_ACCOUNT`. O ambiente é definido pelo prefixo da credencial (`mp_test_*` / `mp_live_*`), não pela variável

### 🔒 Segurança

* **Crítico — validação de assinatura de webhook inoperante.** O HMAC usava o *hash* do secret como chave (a doc exige o secret cru) e ignorava o prefixo `sha256=`, de modo que nenhuma assinatura legítima era aceita. Integradores que contornassem a falha desativando a verificação ficariam com o endpoint aberto a webhooks forjados de pagamento confirmado
* **Crítico — três CVEs na dependência principal.** `saloonphp/saloon` `^3.14.2` estava afetado por CVE-2026-33942 (desserialização insegura), CVE-2026-33182 (SSRF e vazamento de credencial via URL absoluta) e CVE-2026-33183 (path traversal). Agravado por `minimum-stability: dev`, que fazia o projeto rodar `v3.x-dev` — uma branch, não uma release
* **Alto — vazamento de `client_secret` e access token.** O secret era público e o token viajava na `Response` guardada pela exceção, aparecendo em `var_dump`, `print_r`, `serialize` e logs de erro. Agora ambos saem como `[redacted]`
* **Alto — redirects seguidos silenciosamente.** O corpo da requisição (em saques, CPF e chave PIX) podia ser entregue ao destino de um `Location` arbitrário
* **Médio — path traversal** via identificadores concatenados crus na URL, alcançável pelo `externalReference`
* **Médio — `baseUrl` aceitava `http`**, o que enviaria credenciais em texto claro
* **Médio — sem proteção contra replay de webhook**; agora disponível via `timestampHeader` + `toleranceInSeconds`
* **Baixo —** webhook malformado causava erro não tratado, e `JsonException` era lançada ao montar a exceção para respostas não-JSON

### ✨ Adicionado

* Rotas novas da v2: `GET /account`, `GET /projects/{id}`, `POST /transactions/{id}/refund`, `GET /balance-blocks`, `POST /sub-accounts` e `GET /sub-accounts`
* Subcontas: header `X-Sub-Account` via `forSubAccount()`, que devolve um novo connector reaproveitando o token
* Split de pagamento no `POST /pix`, com o DTO `Dtos\Pix\SplitAllocation`
* Idempotência (`Idempotency-Key`) em `POST /pix` e `POST /withdrawals`
* Hierarquia de exceções sobre o envelope de erro documentado — `BadRequest`, `Unauthorized`, `Forbidden`, `NotFound`, `Conflict`, `UnprocessableEntity`, `TooManyRequests`, `ServerError` e `AuthenticationFailed`, todas sob `ApiException`, com `statusCode`, `getDetails()`, `getTraceId()`, `getErrorCode()` e `toArray()`. Opcionalmente lançadas de forma automática via `throwOnErrors: true`
* Exceções de pré-requisição comunicadas pelo tipo, sem texto: `MissingCredentials`, `InvalidBaseUrl`, `MissingWebhookSecret`, `MalformedWebhookPayload`, `InvalidWebhookEvent` e `ExpiredWebhookTimestamp`
* DTOs `BalanceAmount`, `BalanceBlock` e `SubAccount`; `Project` ganhou `updatedAt`
* Campos novos mapeados: `e2eId`, `counterpartName` e `counterpartDocument` em transações; `pspPaymentId`, `pspCredentialId` e `split` no PIX; `projectId` e `externalReference` em saques
* README reescrito com método HTTP, endpoint, parâmetros, exemplo de resposta e erros possíveis por rota
* `composer audit` no CI, para que uma CVE nova quebre o build

### 🐛 Corrigido

* Eventos de webhook desatualizados: de 4 para os 9 da v2, incluindo `payment_expired`, `withdrawal_reversed` e os três `balance_block_*`. Com validação de tipo ligada, eventos legítimos — inclusive os de bloqueio de saldo, que têm prazo de defesa — eram descartados em silêncio
* Campo `subAccount` do webhook, ausente no `WebhookEvent`
* O nível de correção de erro do QR Code nunca era aplicado: o código passava a opção `outputLevel`, inexistente, em vez de `eccLevel`. Quem pedia `EccLevel::H` recebia o padrão
* `getcreatedAt()` e `getpaidAt()` renomeados para o case correto (sem quebra, pois PHP não diferencia case em métodos)
* Mensagens de erro: o SDK não gera mais texto próprio nem valida regras de negócio localmente — a mensagem é sempre a devolvida pelo pague.dev, evitando divergir da API quando ela mudar
* Teste duplicado de `Withdrawals\Create` consolidado

### 📦 Dependências

* `saloonphp/saloon` `^3.14.2` → `^4.0`
* `chillerlan/php-qrcode` `^5.0.5` → `^6.0.1`
* `phpunit/phpunit` `^10.3.2` → `^12.0`
* `minimum-stability` `dev` → `stable`
* Permissões mínimas (`contents: read`) nos workflows de teste e auto-merge do Dependabot restrito a `patch`

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v2.0.0...v3.0.0

## v2.0.0 - 2026-02-24

### What's Changed

* feat: implementado pix estático e dinâmico e adicionado o mapeamento do atributo qrCodeBase64 nas respostas dos mesmos by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/11

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.7.0...v2.0.0

## v1.7.0 - 2026-02-20

### What's Changed

* feat: refatoração de código e implementado funcionalidade de withdrawals by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/10

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.6.0...v1.7.0

## v1.6.0 - 2026-02-13

### What's Changed

* feat: implementado endpoint de métricas by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/9

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.5.0...v1.6.0

## v1.5.0 - 2026-02-09

### What's Changed

* feat: adequação para user agent ser customizável, envio de headers extra e refatoração de código by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/8

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.4.0...v1.5.0

## v1.4.0 - 2026-02-07

### What's Changed

* feat: implementado geração de qr code e refatoração de código by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/7

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.3.0...v1.4.0

## v1.3.0 - 2026-02-06

### What's Changed

* feat: implementado testes de integração e ajustado para rodar testes também em PHP 8.5 by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/6

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.2.1...v1.3.0

## v1.2.1 - 2026-02-06

### What's Changed

* fix: adequação para realizar a validação da assinatura dos webhooks corretamente by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/5

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.2.0...v1.2.1

## v1.2.0 - 2026-02-06

### What's Changed

* fix: refatoração de testes e refatoração pra usar objeto customer em create pix e validação de customer vs customerId by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/4

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.1.1...v1.2.0

## v1.1.1 - 2026-02-06

### What's Changed

* fix: url base da api by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/3

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.1.0...v1.1.1

## v1.1.0 - 2026-02-05

### What's Changed

* feat: implementado parse e validação de eventos webhook by @Pr3d4dor in https://github.com/MountBit/pague-dev-php-sdk/pull/2

**Full Changelog**: https://github.com/MountBit/pague-dev-php-sdk/compare/v1.0.0...v1.1.0

## v1.0.0 - Release inicial - 2026-02-05

- Release inicial
