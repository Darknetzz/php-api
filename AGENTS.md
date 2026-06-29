# Agent guide — php_api

Customizable PHP API framework. Requests hit `index.php`, which loads settings, validates the endpoint, and dispatches to `api_*` functions via reflection.

## Architecture

```
index.php          → entry point (routing, validation, JSON response)
api_base.php       → core logic (auth, cooldown, logging, callFunction)
api_settings.php   → loads settings/*.php
api_endpoints.php  → loads endpoints/*.php
api_keys.php       → loads keys (file or DB) → defines API_KEYS
api_aliases.php    → loads aliases/*.php
lib/ApiKeyStore.php → SQLite/Turso key storage (SHA-256 hashes only)
lib/bootstrap.php  → getApiKeyStore(), auth header helpers (loaded via api_base.php)
bin/api-keys.php   → CLI: list, create, disable keys
bin/migrate-keys.php → migrate keys/custom_api_keys.php → DB
```

Request flow: `?endpoint=foo` → resolves to `api_foo()` → returns JSON via `api_response()`.

## API key storage

Keys can be stored in PHP files (legacy) or in SQLite/Turso (in progress / preferred).

| `KEY_STORE_DRIVER` | Source | Notes |
|--------------------|--------|-------|
| `php` (default) | `keys/custom_*.php` via `addAPIKey()` | Plaintext keys in `$apikeys` array |
| `sqlite` | `data/api.db` | Only SHA-256 hashes stored; plaintext shown once at create/migrate |
| `turso` | libSQL cloud | Requires `KEY_STORE_URL`, `KEY_STORE_TOKEN` (or `TURSO_AUTH_TOKEN`) |

Configure in `settings/my_custom_settings.php` or `settings/custom_*.php`:

```php
"KEY_STORE_DRIVER" => "sqlite",
"KEY_STORE_DSN"    => "",  // empty = {api_root}/data/api.db
```

When `KEY_STORE_DRIVER` is `sqlite` or `turso`:
- `api_keys.php` loads keys from DB via `ApiKeyStore::loadAll()` (options only; no plaintext in memory)
- `apikey_validate()` hashes the incoming key and looks up by `key_hash`
- `addAPIKey()` writes to DB if the name does not exist (used during migration)
- Empty store → error prompting `php bin/migrate-keys.php`

**CLI**

```bash
php bin/migrate-keys.php              # import keys/custom_api_keys.php
php bin/migrate-keys.php --rotate     # migrate with new random keys
php bin/migrate-keys.php --dry-run
php bin/api-keys.php list
php bin/api-keys.php create MyKey --endpoint=foo --no-timeout
php bin/api-keys.php disable MyKey
```

**Schema** (`lib/ApiKeyStore.php`): `api_keys` table with `name`, `key_hash`, `options` (JSON), `enabled`, `created_at`, `last_used_at`.

**Tests:** `vendor/bin/phpunit` — see `tests/ApiKeyStoreTest.php`.

## Where to change things

| Task | Location | Avoid editing |
|------|----------|---------------|
| Settings | `settings/my_custom_settings.php` or `settings/custom_*.php` | `api_settings.php`, `settings/default_settings.php` |
| API keys (file mode) | `keys/my_custom_keys.php` or `keys/custom_*.php` | `api_keys.php` |
| API keys (DB mode) | `bin/api-keys.php`, `lib/ApiKeyStore.php` | — |
| Endpoints | `endpoints/my_custom_endpoints.php` or `endpoints/custom_*.php` | `api_endpoints.php` |
| Example endpoints/data | `endpoints/examples/` (tracked; `custom_*` in `endpoints/` is gitignored) | — |
| Aliases | `aliases/my_custom_aliases.php` or `aliases/custom_*.php` | `api_aliases.php` |
| Core behavior | `api_base.php` only when necessary | — |

Gitignored deployment files: `custom_*` in config folders, `data/` (SQLite DB), `*.log`, `*.json`.

## Conventions

**Endpoints**
- Function names must be prefixed with `api_` (called as `?endpoint=foo` for `api_foo`).
- Always return an array.
- Parameters map from query/body keys; required params have no default value.
- Do **not** `require` `lib/bootstrap.php` or other core files — use globals/constants (`API_KEYS`, etc.) provided by the loader chain.

**API keys**
- File mode: register with `addAPIKey(name:, key:, options:)` in a keys file.
- DB mode: prefer CLI (`bin/api-keys.php create`) or migration from existing PHP keys file.
- Options: `allowedEndpoints`, `disallowedEndpoints`, `noTimeOut`, `notify`, `log_write`, `cooldown`, `sleep`.
- Auth accepts `apikey` query param or headers: `apikey`, `X-API-Key`, `Authorization: Bearer …`.

**Settings**
- Override defaults via `$customs = [...]` in settings files, or define constants directly.
- See `settings/default_settings.php` for all available options.

**Aliases**
- Map main function names to alternate names in `$aliases` arrays.

## Security

Read `SECURITY.md` before changing auth, IP handling, or redirects.

- Endpoints are protected by default (`WHITELIST_MODE`); only `OPEN_ENDPOINTS` are public.
- Endpoint names are validated (`^[a-zA-Z0-9_]+$`).
- DB mode stores only SHA-256 hashes; validation uses `hash_equals` in file mode.
- Use `PRODUCTION_MODE`, `TRUST_PROXY`, and `CORS_ALLOW_ORIGIN` appropriately in production.
- Never commit real API keys; `keys/custom_*.php` and `data/api.db` are gitignored.

## Agent guidelines

1. Prefer extending config folders over modifying core `api_*.php` loaders.
2. For key-store work, change `lib/ApiKeyStore.php` and wire through `api_keys.php` / `api_base.php`; keep file-mode fallback working when `KEY_STORE_DRIVER=php`.
3. Keep changes minimal and match existing PHP style (named args, array returns, header comments).
4. Do not add secrets to tracked files; use `custom_*` files, env vars, or the DB store.
5. Test endpoints via curl: `?endpoint=<name>&apikey=<key>` or `apikey` header.
6. Run `vendor/bin/phpunit` after changes to `lib/ApiKeyStore.php` or auth flow.
7. Optional dev UI: `api_gui.php` (loaded when no endpoint is given).

## Stack

- PHP 8.1+ (Composer for PHPUnit; optional `turso/libsql` for Turso)
- Apache/nginx web server
- SQLite (PDO) for local key store
- Some endpoints may use Composer deps under `endpoints/` (e.g. faker)
