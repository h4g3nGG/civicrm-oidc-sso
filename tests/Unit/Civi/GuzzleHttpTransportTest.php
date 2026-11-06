<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Civi;

use Civi\OidcSso\Civi\GuzzleHttpTransport;
use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\HttpRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class GuzzleHttpTransportTest extends TestCase
{
    public function testSendsHardenedRequestOptions(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}')]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $transport = new GuzzleHttpTransport(new Client(['handler' => $stack]));

        $response = $transport->send(new HttpRequest(
            'POST',
            'https://id.example.org/token',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            'grant_type=authorization_code',
        ));

        self::assertSame(200, $response->statusCode);
        self::assertSame('{"ok":true}', $response->body);
        self::assertCount(1, $history);
        self::assertFalse($history[0]['options']['http_errors']);
        self::assertFalse($history[0]['options']['allow_redirects']);
        self::assertTrue($history[0]['options']['verify']);
        self::assertSame(5.0, $history[0]['options']['connect_timeout']);
        self::assertSame(10.0, $history[0]['options']['timeout']);
    }

    public function testDoesNotFollowRedirects(): void
    {
        $mock = new MockHandler([new Response(302, ['Location' => 'https://other.example.org/'])]);
        $transport = new GuzzleHttpTransport(new Client(['handler' => $mock]));

        $response = $transport->send(new HttpRequest('GET', 'https://id.example.org/data', [], ''));
        self::assertSame(302, $response->statusCode);
    }

    public function testRejectsOversizedResponse(): void
    {
        $mock = new MockHandler([new Response(200, [], str_repeat('x', 1025))]);
        $transport = new GuzzleHttpTransport(new Client(['handler' => $mock]), 1024);

        $this->expectException(OidcValidationException::class);
        $transport->send(new HttpRequest('GET', 'https://id.example.org/data', [], ''));
    }

    public function testRejectsInsecureUrlBeforeRequest(): void
    {
        $mock = new MockHandler();
        $transport = new GuzzleHttpTransport(new Client(['handler' => $mock]));

        $this->expectException(OidcValidationException::class);
        $transport->send(new HttpRequest('GET', 'http://id.example.org/data', [], ''));
    }

    public function testRejectsHeaderInjection(): void
    {
        $mock = new MockHandler();
        $transport = new GuzzleHttpTransport(new Client(['handler' => $mock]));

        $this->expectException(OidcValidationException::class);
        $transport->send(new HttpRequest(
            'GET',
            'https://id.example.org/data',
            ['X-Test' => "value\r\nX-Injected: yes"],
            '',
        ));
    }
}
