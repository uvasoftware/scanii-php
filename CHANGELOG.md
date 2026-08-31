# Changelog

## [6.4.0] — split result and trace deletion

### Added

- `ScaniiClient::delete(string $id): void` — deletes a previously processed file result (`DELETE /files/{id}`). The trace is left intact.
- `ScaniiClient::deleteTrace(string $id): void` — deletes the processing trace for a previously processed file (`DELETE /files/{id}/trace`).
- `ScaniiClient::VERSION` bumped to `6.4.0`.

## [6.3.1] — dependency refresh

### Changed

- Bumped CI actions: `actions/checkout` v4 → v7, `actions/cache` v4 → v6.
  (Dev dependencies float within `composer.json` ranges — `composer.lock` is intentionally
  not committed for this library, so phpunit resolves to the latest 11.x at install time;
  runtime requirements — `php`, `ext-curl`, `ext-json` — are unchanged.)
- `ScaniiClient::VERSION` constant bumped to `6.3.1`.

## [6.3.0] — deprecate AUTO endpoint

### Deprecated

- `ScaniiTarget::AUTO` — latency-based routing does not guarantee which region processes your
  data. Use an explicit regional constant (`ScaniiTarget::US1`, `ScaniiTarget::EU1`, etc.)
  for data residency compliance. Will be removed in a future major version.
- Calling `ScaniiClient::create()` or `ScaniiClient::createFromToken()` without an explicit
  `$target` (or with `ScaniiTarget::AUTO`) now emits an `E_USER_DEPRECATED` notice.

### Fixed

- `ScaniiClient::VERSION` constant corrected from `6.1.0` to `6.3.0` (was stale since 6.2.0).

## [6.2.0] — v2.2 surface

### Added

- `ScaniiClient::retrieveTrace(string $id): ?ScaniiTraceResult` — retrieves the
  ordered processing event trace for a scan via `GET /files/{id}/trace`. Returns
  `null` on 404 (no trace for that id). v2.2 preview surface; API shape may
  shift before marked stable.
- `ScaniiClient::processFromUrl(string $location, ?string $callback, ?array $metadata): ScaniiProcessingResult` —
  submits a URL for synchronous scanning via `POST /files` with `location` as a
  multipart/form-data field. Distinct from `fetch()`, which submits to
  `/files/fetch` for asynchronous server-side fetching. `$location` must be a
  string URL. v2.2 preview surface.
- `Scanii\Models\ScaniiTraceResult` — new result class with `resourceId`,
  `events` (`list<ScaniiTraceEvent>`), and the inherited `ScaniiResult` header
  fields.
- `Scanii\Models\ScaniiTraceEvent` — new model with `?string $timestamp` and
  `string $message`.

### Deprecated

- `ScaniiProcessingResult::$error` — the server never populates this field on
  successful responses; errors arrive as `ScaniiException` subclasses on non-2xx
  HTTP responses. The field is retained for backwards compatibility. Will be
  removed in a future major version.

---

## v6.1.0 — Streaming standardization

### Added

- `ScaniiClient::processStream($stream, $filename, $contentType, $metadata, $callback)` — submit a PHP stream resource for synchronous scanning. `$stream` must be a PHP stream resource (`fopen()`, `tmpfile()`, `fopen('php://temp', 'r+')`, etc.). Content is buffered through `php://temp` (auto-spills to disk above 2 MiB) before upload, so memory pressure is bounded.
- `ScaniiClient::processAsyncStream($stream, $filename, $contentType, $metadata, $callback)` — stream-based equivalent for async scanning.

The existing `process($path, ...)` and `processAsync($path, ...)` are unchanged — path-based callers have no migration to do.

---

## v6.0.0 — Rebrand and rewrite

First release under the new Packagist coordinate `scanii/scanii-php`.
The PHP namespace stays `Scanii\…`; the migration is the install line.

### Breaking

- **Composer package renamed.** `uvasoftware/scanii-php` → `scanii/scanii-php`.
  The PHP namespace is unchanged.
- **Guzzle is gone.** The HTTP layer is `ext-curl` only. Runtime requires
  exactly: `php >=8.4`, `ext-curl`, `ext-json`. No userland dependencies.
- **PSR-4 autoload.** Sources moved from `src/Scanii/…` to `src/…`.
- **PHP 8.4+ required.** PHP 8.3 and earlier reach EOL during this release
  cycle and are unsupported.
- **Result objects now expose `readonly` public properties** instead of
  getters:
  - `getFindings()` → `$result->findings`
  - `getResourceId()` / `getId()` → `$result->resourceId`
  - `getContentType()` → `$result->contentType`
  - …and so on for every accessor
- **`fetch()` argument order** changed: `(location, metadata = [], callback =
  null)` (previously `(location, callback, metadata = [])`). Mirrors
  `process()` / `processAsync()`.
- **`process()` / `processAsync()`** gain an explicit nullable `?string
  $callback` last argument.
- **Result types are split.** `process` returns
  `ScaniiProcessingResult`; `processAsync` and `fetch` return
  `ScaniiPendingResult`; `createAuthToken` / `retrieveAuthToken` return
  `ScaniiAuthToken`. `ScaniiResult` is now an abstract base.
- **Errors throw `Scanii\ScaniiException`** (and subclasses
  `ScaniiAuthException` for HTTP 401/403, `ScaniiRateLimitException` for
  HTTP 429) instead of Guzzle exceptions. Per SDK Principle 3, the SDK
  itself does not retry on rate-limit responses — the caller does.
- **API path pinned to `/v2.2`** server-side. Targets carry only the host;
  the SDK owns the path prefix.
- **Region constants** are pure hosts (`ScaniiTarget::US1` is
  `https://api-us1.scanii.com`, no `/v2.1` suffix). Pass any string URL
  for custom endpoints.

### Removed

- Guzzle, `guzzlehttp/promises`, `psr/*`, every transitive dep — the
  install set shrinks to PHP itself plus `phpunit/phpunit` (dev-only).
- `Scanii\Models\User` and `Scanii\Models\ApiKey` standalone classes —
  replaced by the parity types `ScaniiAccountInfoUser` and
  `ScaniiAccountInfoApiKey`, nested logically inside `ScaniiAccountInfo`.
- `$verbose` constructor flag and the `log()` helper — diagnostics belong
  in the caller's logger, not in the SDK.
- `bundled composer.phar` — install Composer the standard way.

### Added

- Full PHPUnit 11 test suite covering ping, process, processAsync, fetch,
  retrieve, auth-token CRUD, callback delivery, and credential validation.
- Local-malware UUID fixture (matches `content.malicious.local-test-file`)
  used in place of EICAR — Windows Defender / macOS Gatekeeper on CI
  runners do not quarantine it.
- PR CI matrix: PHP 8.4 + 8.5 × ubuntu-latest, macos-latest,
  windows-latest. scanii-cli boot via `scanii/setup-cli-action@v1`.
- `release.yml` workflow on `release: published` — Packagist auto-publish
  is webhook-driven; this workflow only acknowledges the release.

### Migration

See README "Migration from `uvasoftware/scanii-php`".

---

## v5.x and earlier

The 1.x–5.x line of `uvasoftware/scanii-php` is preserved at
[github.com/scanii/scanii-php](https://github.com/scanii/scanii-php) under
its prior tags. A bridge release on the old coordinate redirects users to
this package.
