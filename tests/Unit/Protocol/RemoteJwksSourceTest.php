<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\HttpRequest;
use Civi\OidcSso\Protocol\HttpResponse;
use Civi\OidcSso\Protocol\HttpTransport;
use Civi\OidcSso\Protocol\JsonDocumentFetcher;
use Civi\OidcSso\Protocol\RemoteJwksSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RemoteJwksSourceTest extends TestCase
{
    public function testCachesAndForceRefreshesJwks(): void
    {
        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);
        $transport->expects(self::exactly(2))
            ->method('send')
            ->with(self::callback(
                static fn(HttpRequest $request): bool => $request->url === 'https://id.example.org/jwks',
            ))
            ->willReturnOnConsecutiveCalls(
                new HttpResponse(200, ['Content-Type' => 'application/json'], '{"keys":[{"kid":"one"}]}'),
                new HttpResponse(200, ['Content-Type' => 'application/json'], '{"keys":[{"kid":"two"}]}'),
            );
        $source = new RemoteJwksSource(new JsonDocumentFetcher($transport), 'https://id.example.org/jwks');

        self::assertSame('one', $source->get()['keys'][0]['kid']);
        self::assertSame('one', $source->get()['keys'][0]['kid']);
        self::assertSame('two', $source->get(true)['keys'][0]['kid']);
    }
}
