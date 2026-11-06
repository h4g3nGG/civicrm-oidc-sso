<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\HttpResponse;
use Civi\OidcSso\Protocol\HttpTransport;
use Civi\OidcSso\Protocol\JsonDocumentFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class JsonDocumentFetcherTest extends TestCase
{
    public function testFetchesJsonObject(): void
    {
        $fetcher = new JsonDocumentFetcher($this->transport(
            new HttpResponse(200, ['content-type' => 'application/jwk-set+json'], '{"keys":[]}'),
        ));

        self::assertSame(['keys' => []], $fetcher->fetch('https://id.example.org/jwks'));
    }

    #[DataProvider('invalidResponseProvider')]
    public function testRejectsInvalidResponse(HttpResponse $response): void
    {
        $this->expectException(OidcValidationException::class);
        (new JsonDocumentFetcher($this->transport($response)))->fetch('https://id.example.org/data');
    }

    /** @return iterable<string, array{HttpResponse}> */
    public static function invalidResponseProvider(): iterable
    {
        yield 'status' => [new HttpResponse(404, ['Content-Type' => 'application/json'], '{}')];
        yield 'content type' => [new HttpResponse(200, ['Content-Type' => 'text/html'], '{}')];
        yield 'invalid JSON' => [new HttpResponse(200, ['Content-Type' => 'application/json'], '{')];
        yield 'JSON list' => [new HttpResponse(200, ['Content-Type' => 'application/json'], '[]')];
    }

    /** @return HttpTransport&MockObject */
    private function transport(HttpResponse $response): HttpTransport
    {
        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);
        $transport->expects(self::once())->method('send')->willReturn($response);
        return $transport;
    }
}
