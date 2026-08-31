# scanii-php

Official PHP SDK for the [Scanii](https://www.scanii.com) content processing API.

## SDK Principles

1. **Light.** Zero runtime dependencies, stdlib only.
2. **Up to date.** Always current with the latest Scanii API.
3. **Integration-only.** Wraps the REST API — retries, concurrency, and batching are the caller's responsibility.

The only stdlib extensions required are `ext-curl` and `ext-json`, both shipped by default with every official PHP distribution (including the Windows builds).

## Install

```bash
composer require scanii/scanii-php
```

Requires PHP 8.4 or newer.

## Quickstart

```php
use Scanii\ScaniiClient;
use Scanii\ScaniiTarget;

$client = ScaniiClient::create('your-api-key', 'your-api-secret', ScaniiTarget::US1);

// Scan a file from disk:
$result = $client->process('/path/to/file');
echo implode(', ', $result->findings);
```

`ScaniiClient::create` returns a thread-friendly client that you can reuse across requests; the constructor performs no I/O.

### Scanning from a stream

Pass any PHP stream resource — `fopen()`, `tmpfile()`, `php://temp`, etc.:

```php
// From a temp file / in-memory stream:
$stream = fopen('php://temp', 'r+');
fwrite($stream, $content);
rewind($stream);

$result = $client->processStream($stream, 'upload.bin');
echo implode(', ', $result->findings);
fclose($stream);
```

## API

| Method | REST | Returns |
|---|---|---|
| `process($path, $metadata, $callback)` | `POST /files` | `ScaniiProcessingResult` |
| `processStream($stream, $filename, $contentType, $metadata, $callback)` | `POST /files` | `ScaniiProcessingResult` |
| `processAsync($path, $metadata, $callback)` | `POST /files/async` | `ScaniiPendingResult` |
| `processAsyncStream($stream, $filename, $contentType, $metadata, $callback)` | `POST /files/async` | `ScaniiPendingResult` |
| `processFromUrl($location, $callback, $metadata)` | `POST /files` | `ScaniiProcessingResult` |
| `fetch($location, $metadata, $callback)` | `POST /files/fetch` | `ScaniiPendingResult` |
| `retrieve($id)` | `GET /files/{id}` | `ScaniiProcessingResult` |
| `retrieveTrace($id)` | `GET /files/{id}/trace` | `?ScaniiTraceResult` |
| `delete($id)` | `DELETE /files/{id}` | `bool` |
| `deleteTrace($id)` | `DELETE /files/{id}/trace` | `bool` |
| `ping()` | `GET /ping` | `bool` |
| `createAuthToken($timeoutSeconds)` | `POST /auth/tokens` | `ScaniiAuthToken` |
| `retrieveAuthToken($id)` | `GET /auth/tokens/{id}` | `ScaniiAuthToken` |
| `deleteAuthToken($id)` | `DELETE /auth/tokens/{id}` | `void` |
| `retrieveAccountInfo()` | `GET /account.json` | `ScaniiAccountInfo` |

`delete()` and `deleteTrace()` are independent: deleting a processing result leaves its
trace readable, and deleting a trace leaves the result readable. To erase a scan
entirely, call both.

Full API reference: <https://scanii.github.io/openapi/v22/>.

## Regional endpoints

| Constant | Endpoint |
|---|---|
| `ScaniiTarget::US1` | `https://api-us1.scanii.com` |
| `ScaniiTarget::EU1` | `https://api-eu1.scanii.com` |
| `ScaniiTarget::EU2` | `https://api-eu2.scanii.com` |
| `ScaniiTarget::AP1` | `https://api-ap1.scanii.com` |
| `ScaniiTarget::AP2` | `https://api-ap2.scanii.com` |
| `ScaniiTarget::CA1` | `https://api-ca1.scanii.com` |
| ~~`ScaniiTarget::AUTO`~~ | ~~`https://api.scanii.com`~~ — **deprecated**, does not guarantee regional data placement |

Pass any string URL for a custom or local endpoint:

```php
$client = ScaniiClient::create('key', 'secret', 'http://localhost:4000');
```

## Local development with scanii-cli

Run the integration tests against a local mock server — no real credentials needed:

```bash
docker run -d --name scanii-cli -p 4000:4000 ghcr.io/scanii/scanii-cli:latest server
composer install
composer test
```

Test credentials: key `key`, secret `secret`, endpoint `http://localhost:4000`.

## Migration from `uvasoftware/scanii-php`

```diff
-"uvasoftware/scanii-php": "^5.0"
+"scanii/scanii-php": "^6.0"
```

The PHP namespace is unchanged (`Scanii\…`). Other notable changes:

- The runtime no longer depends on Guzzle. Only `ext-curl` and `ext-json` (both stdlib).
- The autoloader uses PSR-4 — `src/Scanii/...` files moved up to `src/...`.
- Result objects use **public `readonly` properties** instead of getters:

  ```diff
  - $r->getFindings()
  + $r->findings
  ```
- `process` / `processAsync` / `fetch` now take an explicit nullable `?string $callback` last arg (previously implicit through metadata):

  ```diff
  - $client->fetch($url, $callback, $metadata)
  + $client->fetch($url, $metadata, $callback)
  ```
- Errors throw `Scanii\ScaniiException` (and `ScaniiAuthException`, `ScaniiRateLimitException` subclasses), not Guzzle exceptions.

The old composer coordinate `uvasoftware/scanii-php` is deprecated and will not receive further updates.

## API documentation

See [https://scanii.github.io/openapi/v22/](https://scanii.github.io/openapi/v22/) for the full Scanii API contract.

## License

Apache 2.0 — see [LICENSE](LICENSE).
