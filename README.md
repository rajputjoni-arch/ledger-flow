# Ledger Flow Transfer API

This repository now includes a secure fund transfer API built with Symfony, MySQL, and Redis.

## Architecture

- `MySQL` stores account and transfer state.
- `Redis` provides caching and idempotency support.
- `Doctrine ORM` manages entities and transactional consistency.
- `Pessimistic locking` ensures transaction integrity under concurrent transfer requests.
- `HTTP API key` authentication secures the endpoint.

## Setup

1. Build and start the stack:

```bash
docker compose build
docker compose up -d
```

2. Install PHP dependencies if needed:

```bash
composer install
```

3. Create the database schema:

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

## API

### Transfer funds

POST `/api/v1/transfers`

Headers:

- `X-Api-Key: <your-api-token>` (set via `API_TOKEN` environment variable)
- `X-Idempotency-Key: <request-id>`
- `Content-Type: application/json`

Body:

```json
{
  "fromAccountId": "acct-1",
  "toAccountId": "acct-2",
  "amount": 100.00,
  "currency": "USD"
}
```

Response:

```json
{
  "status": "success",
  "transfer": {
    "transactionId": "...",
    "fromAccount": { ... },
    "toAccount": { ... },
    "amount": "100.00",
    "currency": "USD",
    "createdAt": "2026-06-23T..."
  }
}
```

## Testing

Run the functional tests with PHPUnit:

```bash
docker compose exec php php bin/phpunit
```

## Security

- Copy `.env.example` to `.env.local` and provide real values:
  ```bash
  cp .env.example .env.local
  # Edit .env.local and set API_TOKEN, APP_SECRET, DATABASE_URL, REDIS_URL
  ```
- For production, rotate `API_TOKEN` and `APP_SECRET` regularly and use a secrets manager (Symfony Secrets, HashiCorp Vault, AWS Secrets Manager, etc.).
- Use HTTPS only in production.
- Implement rate limiting and IP allowlisting for API access.

## Notes

- `X-Idempotency-Key` prevents duplicate transfer processing on retries.
- Redis is used for caching and idempotent transfer lookups.
