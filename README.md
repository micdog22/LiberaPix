# LiberaPIX — Paywall PIX com liberação automática

**Autor:** Michael Douglas (MicDog)  
**Stack:** PHP 8.1+, SQLite, cURL, Composer  
**Licença:** MIT

Libera downloads/links **somente após pagamento via PIX** (Mercado Pago).  
Ideal para **produtos digitais** (PDF, ZIP, licença, curso, acesso temporário, etc.).

## Recursos
- Checkout PIX (gera QR e “copia e cola”)
- **Webhook do Mercado Pago**: confirma pagamento e libera automaticamente
- **Token de download com validade** (expiração configurável)
- Banco **SQLite** pronto para uso (ou adapte para MySQL/Postgres)
- Simples de hospedar (Hostgator/Hostinger) ou local com `php -S`
- Estrutura limpa para subir no GitHub como projeto open-source

---
## Estrutura

```
LiberaPIX/
├─ public/
│  ├─ index.php            # Landing + checkout
│  ├─ status.php           # Página que acompanha o status (polling)
│  ├─ download.php         # Entrega o arquivo com token
│  ├─ api/
│  │  ├─ checkout.php      # -> src/controllers/CheckoutController.php
│  │  └─ status.php        # -> src/controllers/StatusController.php
│  └─ webhook/
│     └─ mercadopago.php   # -> src/controllers/WebhookController.php
├─ src/
│  ├─ bootstrap.php        # Autoload, .env, DB init, migrations
│  ├─ Config.php
│  ├─ Database.php
│  ├─ Helpers.php
│  ├─ TokenService.php
│  ├─ MercadoPagoClient.php
│  └─ controllers/
│     ├─ CheckoutController.php
│     ├─ StatusController.php
│     ├─ WebhookController.php
│     └─ DownloadController.php
├─ storage/
│  ├─ products/            # Coloque aqui seu arquivo/DLC/zip
│  ├─ logs/
│  └─ database.sqlite      # Criado automaticamente
├─ migrations/001_create_tables.sql
├─ Postman/                # (opcional) coleção para testes
├─ .env.example
├─ composer.json
├─ LICENSE
└─ README.md
```

---
## Comece agora (local)

> Requisitos: PHP 8.1+, Composer, cURL.

```bash
cp .env.example .env
# edite o .env e coloque seu MP_ACCESS_TOKEN (Sandbox ou Prod)

composer install
# roda migrações automaticamente via script post-install (se necessário: php src/bootstrap.php --migrate)

composer run serve
# abre http://localhost:8080
```

**Importante:** configure o **Webhook do Mercado Pago** apontando para:
```
{APP_URL}/webhook/mercadopago.php
```
(Ex.: `http://localhost:8080/webhook/mercadopago.php` via túnel/Ngrok, ou seu domínio público em produção.)

---
## Configuração (.env)

```
APP_URL=http://localhost:8080
MP_ACCESS_TOKEN=SEU_TOKEN_MERCADO_PAGO
SQLITE_PATH=storage/database.sqlite
DOWNLOAD_TOKEN_TTL_HOURS=24
SUPPORT_EMAIL=suporte@exemplo.com
```

- **APP_URL**: URL pública SEM barra final.
- **MP_ACCESS_TOKEN**: crie em **Credenciais** do Mercado Pago (Sandbox para testes).
- **DOWNLOAD_TOKEN_TTL_HOURS**: validade do link (ex.: 24 = 24h).
- **SQLITE_PATH**: caminho do banco SQLite.

---
## Fluxo de ponta a ponta

1. **Cliente abre** `public/index.php`
2. Clica “Comprar com PIX” → `POST /api/checkout.php`
   - Cria `payment` via API MP (`payment_method_id = pix`)
   - Retorna QR code (PNG base64) + *copia e cola* + `order_id`
3. **Cliente paga** no app do banco (PIX)
4. Mercado Pago **envia webhook** → `public/webhook/mercadopago.php`
   - Buscamos o pagamento por `payment_id` na API do MP
   - Se `status = approved` → marcamos pedido como `paid`
   - Geramos **token de download** com validade (`TokenService`)
5. Cliente abre `status.php?order_id=...` (polling a cada 3s)
   - Ao ficar `paid`, aparece botão **Baixar arquivo** com token
6. `download.php?token=...` valida (pago + não expirado) e entrega o arquivo

---
## Segurança & Boas Práticas

- **Webhook idempotente:** gravamos chaves em `idempotency_keys` para evitar reprocessamento.
- **Nunca confie no payload:** confirmamos status consultando a **API oficial** do Mercado Pago.
- **Links temporários:** token expira (configurável). Gere **um token por compra**.
- **Servir arquivos com segurança:** o exemplo usa `readfile()`. Em produção, prefira:
  - Nginx: `X-Accel-Redirect`
  - Apache: `X-Sendfile`
- **Proteja o diretório de produtos** (fora de `public/`).
- **Logs**: `storage/logs/` (adicione rotação de logs no servidor).
- **HTTPS obrigatório** em produção.

---
## Personalização

- **Preço/Produto:** ajuste em `public/index.php` (ex.: `amount_centavos: 990`).
- **Nome/descrição** do pagamento: `CheckoutController.php`.
- **Expiração** do token: `.env` → `DOWNLOAD_TOKEN_TTL_HOURS`.
- **Múltiplos produtos:** crie coluna/tabela de catálogo e associe `order.product_id`.

---
## Banco de Dados

SQLite por padrão:
- Tabela `orders`: status do pedido, `download_token`, expiração, `mp_payment_id`.
- Tabela `webhook_log`: auditoria do payload recebido.
- Tabela `idempotency_keys`: chaves já processadas.

Para MySQL/Postgres:
- Troque `Database::init()` para PDO correspondente e ajuste o SQL.
- Mantenha as colunas/índices equivalentes.

---
## Integração com Mercado Pago (Notas)

- Endpoint de criação de pagamento: `POST /v1/payments`
  - `payment_method_id = pix`
  - Retorna `point_of_interaction.transaction_data.qr_code` e `qr_code_base64`
- Webhooks: Mercado Pago envia `type=payment` + `data.id` (ou `id`)
  - Sempre confirme via `GET /v1/payments/{id}`
  - Status aprovado → `status = "approved"`

> Dica: para mapear **pedido ↔ pagamento**, salve `payment_id` ao criar o pedido (metadata).  
Este boilerplate já prevê leitura de `metadata.order_id` no webhook.

---
## Deploy (HostGator/Hostinger)

- PHP 8.1+ ativado
- Suba os arquivos para uma pasta fora do `public_html` e aponte o **document root** para `public/`
  - Se não puder, mova a pasta `public/` para `public_html/` e ajuste caminhos
- Permissões de escrita em `storage/`
- Configure o webhook no painel do Mercado Pago para `https://SEU_DOMINIO/webhook/mercadopago.php`

---
## FAQ

**Posso liberar um papel/cargo no Discord ao invés de download?**  
Sim — troque `download.php` por um handler que bate na sua API/Discord ao aprovar.

**Posso liberar um ZIP gigante?**  
Sim — mas sirva com `X-Accel-Redirect`/`X-Sendfile` para eficiência.

**Sandbox vs Produção?**  
Use Sandbox enquanto desenvolve; depois troque o `MP_ACCESS_TOKEN`.

---
## Contribuindo

1. Faça um fork
2. `git checkout -b feature/minha-feature`
3. Envie PR explicando o que mudou e por quê

---
## Autor

**Michael Douglas (MicDog)** — @MicDog  
Projeto open-source para a comunidade.
