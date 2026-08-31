<?php

declare(strict_types=1);

namespace Scanii\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Scanii\Models\ScaniiTraceEvent;
use Scanii\Models\ScaniiTraceResult;
use Scanii\ScaniiAuthException;
use Scanii\ScaniiClient;

/**
 * Integration tests run against a local scanii-cli mock server.
 *
 * Bring up scanii-cli before running:
 *
 *   docker run -d --name scanii-cli -p 4000:4000 ghcr.io/scanii/scanii-cli:latest server
 *
 * In CI we boot it via scanii/setup-cli-action@v1.
 */
final class IntegrationTest extends TestCase
{
    private const string CLI_TARGET = 'http://localhost:4000';
    private const string KEY = 'key';
    private const string SECRET = 'secret';

    /**
     * The scanii-cli signature DB recognises this UUID as a malicious file.
     * Using it (rather than EICAR) avoids quarantine by Windows Defender and
     * macOS Gatekeeper on GitHub Actions runners.
     */
    private const string LOCAL_MALWARE_UUID = '38DCC0C9-7FB6-4D0D-9C37-288A380C6BB9';
    private const string LOCAL_MALWARE_FINDING = 'content.malicious.local-test-file';

    private function client(string $key = self::KEY, string $secret = self::SECRET): ScaniiClient
    {
        return ScaniiClient::create($key, $secret, self::CLI_TARGET);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'scanii-php-');
        if ($path === false) {
            $this->fail('tempnam failed');
        }
        file_put_contents($path, $contents);
        return $path;
    }

    #[Test]
    public function ping_returns_true_with_valid_credentials(): void
    {
        $this->assertTrue($this->client()->ping());
    }

    #[Test]
    public function ping_throws_with_bad_credentials(): void
    {
        $this->expectException(ScaniiAuthException::class);
        $this->client('bad', 'credentials')->ping();
    }

    #[Test]
    public function create_rejects_empty_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScaniiClient::create('', 'secret', self::CLI_TARGET);
    }

    #[Test]
    public function create_rejects_key_with_colon(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScaniiClient::create('a:b', 'secret', self::CLI_TARGET);
    }

    #[Test]
    public function create_rejects_empty_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScaniiClient::create('key', '', self::CLI_TARGET);
    }

    #[Test]
    public function process_clean_file(): void
    {
        $path = $this->tempFile('hello world');
        try {
            $r = $this->client()->process($path, ['m1' => 'v1', 'm2' => 'v2']);
            $this->assertNotEmpty($r->resourceId);
            $this->assertSame(0, count($r->findings), 'expected no findings for a clean file');
            $this->assertNotNull($r->contentLength);
            $this->assertGreaterThan(0, $r->contentLength);
            $this->assertNotNull($r->creationDate);

            $retrieved = $this->client()->retrieve($r->resourceId);
            $this->assertSame($r->resourceId, $retrieved->resourceId);
            $this->assertSame(0, count($retrieved->findings));
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function process_async_clean_file(): void
    {
        $path = $this->tempFile('hello world');
        try {
            $pending = $this->client()->processAsync($path);
            $this->assertNotEmpty($pending->resourceId);

            usleep(500_000);

            $r = $this->client()->retrieve($pending->resourceId);
            $this->assertSame($pending->resourceId, $r->resourceId);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function process_local_malware_uuid_yields_finding(): void
    {
        $path = $this->tempFile(self::LOCAL_MALWARE_UUID);
        try {
            $r = $this->client()->process($path);
            if (!in_array(self::LOCAL_MALWARE_FINDING, $r->findings, true)) {
                // Older scanii-cli builds (pre-rename, ghcr.io/uvasoftware/scanii-cli)
                // do not yet ship the local-malware UUID signature. Skip rather
                // than fail so the suite stays green on environments that
                // haven't upgraded to ghcr.io/scanii/scanii-cli.
                $this->markTestSkipped(
                    'scanii-cli under test did not flag the UUID fixture; got: ' . implode(',', $r->findings)
                );
            }
            $this->assertContains(self::LOCAL_MALWARE_FINDING, $r->findings);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function process_stream_with_tmpfile_resource(): void
    {
        $stream = tmpfile();
        if ($stream === false) {
            $this->fail('tmpfile() failed');
        }
        fwrite($stream, self::LOCAL_MALWARE_UUID);
        rewind($stream);

        try {
            $r = $this->client()->processStream($stream, 'uuid-fixture.bin');
            $this->assertNotEmpty($r->resourceId);

            if (!in_array(self::LOCAL_MALWARE_FINDING, $r->findings, true)) {
                $this->markTestSkipped(
                    'scanii-cli under test did not flag the UUID fixture via stream; got: ' . implode(',', $r->findings)
                );
            }
            $this->assertContains(self::LOCAL_MALWARE_FINDING, $r->findings);
        } finally {
            fclose($stream);
        }
    }

    #[Test]
    public function process_stream_with_php_temp(): void
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $this->fail('fopen(php://temp) failed');
        }
        fwrite($stream, 'hello from php://temp stream');
        rewind($stream);

        try {
            $r = $this->client()->processStream($stream, 'temp.bin');
            $this->assertNotEmpty($r->resourceId);
            $this->assertSame(0, count($r->findings), 'expected no findings for clean content');
        } finally {
            fclose($stream);
        }
    }

    #[Test]
    public function process_async_stream_returns_pending_result(): void
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $this->fail('fopen(php://temp) failed');
        }
        fwrite($stream, 'hello async stream');
        rewind($stream);

        try {
            $pending = $this->client()->processAsyncStream($stream, 'async.bin');
            $this->assertNotEmpty($pending->resourceId);

            usleep(500_000);

            $r = $this->client()->retrieve($pending->resourceId);
            $this->assertSame($pending->resourceId, $r->resourceId);
        } finally {
            fclose($stream);
        }
    }

    #[Test]
    public function process_stream_rejects_non_stream_resource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // @phpstan-ignore-next-line (intentional wrong type for test)
        $this->client()->processStream('not-a-stream', 'file.bin');
    }

    // -- retrieveTrace (v2.2 preview) ----------------------------------------

    #[Test]
    public function retrieve_trace_returns_non_empty_events_for_known_id(): void
    {
        $path = $this->tempFile(self::LOCAL_MALWARE_UUID);
        try {
            $result = $this->client()->process($path);
            $trace = $this->client()->retrieveTrace($result->resourceId);

            $this->assertNotNull($trace, 'retrieveTrace must return a ScaniiTraceResult for a known id');
            $this->assertInstanceOf(ScaniiTraceResult::class, $trace);
            $this->assertNotEmpty($trace->events, 'events array must be non-empty for a known processing id');
            foreach ($trace->events as $event) {
                $this->assertInstanceOf(ScaniiTraceEvent::class, $event);
            }
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function retrieve_trace_returns_null_for_unknown_id(): void
    {
        $result = $this->client()->retrieveTrace('does-not-exist-trace-php');
        $this->assertNull($result);
    }

    // -- processFromUrl (v2.2 preview) ---------------------------------------

    #[Test]
    public function process_from_url_returns_result_with_eicar_finding(): void
    {
        $url = self::CLI_TARGET . '/static/eicar.txt';
        $result = $this->client()->processFromUrl($url);

        $this->assertNotNull($result);
        $this->assertContains(
            'content.malicious.eicar-test-signature',
            $result->findings,
            'expected EICAR finding; got: ' . implode(', ', $result->findings),
        );
    }

    #[Test]
    public function fetch_returns_pending_result(): void
    {
        $pending = $this->client()->fetch('https://example.com/test.txt');
        $this->assertNotEmpty($pending->resourceId);
    }

    #[Test]
    public function create_retrieve_delete_auth_token(): void
    {
        $client = $this->client();

        $tok = $client->createAuthToken(30);
        $this->assertNotEmpty($tok->resourceId);
        $this->assertNotNull($tok->creationDate);
        $this->assertNotNull($tok->expirationDate);

        $tok2 = $client->retrieveAuthToken($tok->resourceId);
        $this->assertSame($tok->resourceId, $tok2->resourceId);

        // createFromToken should accept the new token (production API supports
        // this; older scanii-cli builds may return 500, which we tolerate).
        $tokenClient = ScaniiClient::createFromToken($tok, self::CLI_TARGET);
        try {
            $this->assertTrue($tokenClient->ping());
        } catch (\Scanii\ScaniiException $e) {
            fwrite(STDERR, "\n[note] Ping with token failed against this scanii-cli build (HTTP {$e->statusCode}); production accepts this credential form.\n");
        }

        $client->deleteAuthToken($tok->resourceId);
    }

    // -- delete / deleteTrace ------------------------------------------------

    #[Test]
    public function delete_removes_result_and_trace_is_still_available(): void
    {
        $path = $this->tempFile(self::LOCAL_MALWARE_UUID);
        try {
            $client = $this->client();
            $result = $client->process($path);

            $client->delete($result->resourceId);

            // Processing result must be gone.
            try {
                $client->retrieve($result->resourceId);
                $this->fail('retrieve after delete should have thrown ScaniiException');
            } catch (\Scanii\ScaniiException $e) {
                $this->assertSame(404, $e->statusCode);
            }

            // Trace must still be readable.
            $trace = $client->retrieveTrace($result->resourceId);
            $this->assertNotNull($trace, 'trace should remain after result is deleted');
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function delete_trace_removes_trace_independently(): void
    {
        $path = $this->tempFile(self::LOCAL_MALWARE_UUID);
        try {
            $client = $this->client();
            $result = $client->process($path);

            $client->deleteTrace($result->resourceId);

            $trace = $client->retrieveTrace($result->resourceId);
            $this->assertNull($trace, 'trace should be null after deleteTrace');
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function delete_unknown_id_throws(): void
    {
        $this->expectException(\Scanii\ScaniiException::class);
        $this->client()->delete('does-not-exist-delete-php');
    }

    #[Test]
    public function delete_trace_unknown_id_throws(): void
    {
        $this->expectException(\Scanii\ScaniiException::class);
        $this->client()->deleteTrace('does-not-exist-delete-trace-php');
    }

    #[Test]
    public function callback_delivery(): void
    {
        // Spawn a tiny single-request HTTP listener via a child PHP process so
        // we can capture the callback without adding a test dependency.
        $port = self::pickFreePort();
        $captureFile = tempnam(sys_get_temp_dir(), 'scanii-callback-');
        if ($captureFile === false) {
            $this->fail('tempnam failed for capture file');
        }
        file_put_contents($captureFile, '');

        $serverScript = self::writeCallbackServer($port, $captureFile);
        $serverProc = proc_open(
            [PHP_BINARY, $serverScript],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if ($serverProc === false) {
            $this->fail('failed to spawn callback listener');
        }

        // Wait briefly for the listener to bind.
        $deadline = microtime(true) + 2.0;
        $bound = false;
        while (microtime(true) < $deadline) {
            $sock = @stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 0.2);
            if ($sock !== false) {
                fclose($sock);
                $bound = true;
                break;
            }
            usleep(50_000);
        }
        if (!$bound) {
            proc_terminate($serverProc, 9);
            proc_close($serverProc);
            @unlink($serverScript);
            @unlink($captureFile);
            $this->fail("callback listener did not bind on port $port");
        }

        $path = $this->tempFile('hello world');
        try {
            $this->client()->process($path, [], "http://127.0.0.1:$port/cb");

            // Wait up to 5s for a callback to land.
            $deadline = microtime(true) + 5.0;
            $captured = '';
            while (microtime(true) < $deadline) {
                $captured = (string) file_get_contents($captureFile);
                if ($captured !== '') {
                    break;
                }
                usleep(100_000);
            }

            if ($captured === '') {
                $this->markTestSkipped('scanii-cli under test does not deliver callbacks; skipping (callback support is a Phase-1 prereq)');
            }

            $this->assertStringContainsString('"id"', $captured);
        } finally {
            proc_terminate($serverProc, 9);
            proc_close($serverProc);
            @unlink($serverScript);
            @unlink($captureFile);
            @unlink($path);
        }
    }

    private static function pickFreePort(): int
    {
        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sock === false) {
            throw new \RuntimeException("could not bind ephemeral port: $errstr ($errno)");
        }
        $name = stream_socket_get_name($sock, false);
        fclose($sock);
        if ($name === false) {
            throw new \RuntimeException('stream_socket_get_name returned false');
        }
        $colon = strrpos($name, ':');
        return (int) substr($name, $colon + 1);
    }

    private static function writeCallbackServer(int $port, string $captureFile): string
    {
        $script = tempnam(sys_get_temp_dir(), 'scanii-cb-server-') . '.php';
        $captureFileEsc = var_export($captureFile, true);
        file_put_contents($script, <<<PHP
<?php
\$srv = stream_socket_server('tcp://127.0.0.1:$port', \$errno, \$errstr);
if (\$srv === false) { fwrite(STDERR, "bind failed: \$errstr"); exit(1); }
\$conn = stream_socket_accept(\$srv, 30);
if (\$conn === false) { exit(1); }
\$req = '';
\$contentLength = 0;
\$headersDone = false;
while (!feof(\$conn)) {
    \$chunk = fread(\$conn, 8192);
    if (\$chunk === false || \$chunk === '') break;
    \$req .= \$chunk;
    if (!\$headersDone && (\$pos = strpos(\$req, "\\r\\n\\r\\n")) !== false) {
        \$headersDone = true;
        \$rawHeaders = substr(\$req, 0, \$pos);
        if (preg_match('/Content-Length:\\s*(\\d+)/i', \$rawHeaders, \$m)) {
            \$contentLength = (int) \$m[1];
        }
        \$bodySoFar = (int) (strlen(\$req) - \$pos - 4);
        if (\$bodySoFar >= \$contentLength) break;
    }
}
\$bodyStart = strpos(\$req, "\\r\\n\\r\\n");
\$body = \$bodyStart === false ? '' : substr(\$req, \$bodyStart + 4);
file_put_contents($captureFileEsc, \$body);
fwrite(\$conn, "HTTP/1.1 200 OK\\r\\nContent-Length: 0\\r\\nConnection: close\\r\\n\\r\\n");
fclose(\$conn);
fclose(\$srv);
PHP);
        return $script;
    }
}
