<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Web;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\AuthorizationTransactionFactory;
use Civi\OidcSso\Protocol\AuthorizationTransactionManager;
use Civi\OidcSso\Protocol\IdTokenClaimsValidator;
use Civi\OidcSso\Protocol\TokenExchanger;
use Civi\OidcSso\Protocol\TokenResponse;
use Civi\OidcSso\Protocol\VerifiedIdTokenDecoder;
use Civi\OidcSso\Tests\Unit\Protocol\InMemoryAuthorizationTransactionRepository;
use Civi\OidcSso\Web\AuthorizationCallbackService;
use PHPUnit\Framework\TestCase;

final class AuthorizationCallbackServiceTest extends TestCase
{
    public function testConsumesTransactionExchangesCodeAndValidatesClaims(): void
    {
        [$service, $manager, $repository, $exchanger, $decoder] = $this->fixture();
        $transaction = $manager->begin('/civicrm/home', 'browser', 1700000000);
        $claims = $this->claims($transaction->nonce);
        $exchanger->expects(self::once())->method('exchange')
            ->with('authorization-code', $transaction->codeVerifier)
            ->willReturn(new TokenResponse('access', 'signed-id-token', 'Bearer', 300, null, null));
        $decoder->expects(self::once())->method('verify')->with('signed-id-token')->willReturn($claims);

        $identity = $service->complete(
            $transaction->state,
            'authorization-code',
            null,
            'browser',
            1700000010,
        );

        self::assertSame('/civicrm/home', $identity->returnPath);
        self::assertSame('external-subject', $identity->claims['sub']);
        self::assertSame([], $repository->records);
    }

    public function testProviderErrorStillConsumesTransaction(): void
    {
        [$service, $manager, $repository, $exchanger, $decoder] = $this->fixture();
        $transaction = $manager->begin('/civicrm/home', 'browser', 1700000000);
        $exchanger->expects(self::never())->method('exchange');
        $decoder->expects(self::never())->method('verify');

        try {
            $service->complete($transaction->state, null, 'access_denied', 'browser', 1700000010);
            self::fail('Expected provider error.');
        } catch (OidcValidationException $exception) {
            self::assertStringContainsString('rejected', $exception->getMessage());
        }
        self::assertSame([], $repository->records);
    }

    public function testRejectsCallbackFromDifferentBrowserAndConsumesTransaction(): void
    {
        [$service, $manager, $repository] = $this->fixture();
        $transaction = $manager->begin('/civicrm/home', 'browser', 1700000000);

        $this->expectException(OidcValidationException::class);
        try {
            $service->complete($transaction->state, 'code', null, 'other-browser', 1700000010);
        } finally {
            self::assertSame([], $repository->records);
        }
    }

    /** @return array{AuthorizationCallbackService, AuthorizationTransactionManager, InMemoryAuthorizationTransactionRepository, TokenExchanger&object, VerifiedIdTokenDecoder&object} */
    private function fixture(): array
    {
        $repository = new InMemoryAuthorizationTransactionRepository();
        $manager = new AuthorizationTransactionManager(
            new AuthorizationTransactionFactory(),
            $repository,
            str_repeat('k', 32),
        );
        $exchanger = $this->createMock(TokenExchanger::class);
        $decoder = $this->createMock(VerifiedIdTokenDecoder::class);
        $service = new AuthorizationCallbackService(
            $manager,
            $exchanger,
            $decoder,
            new IdTokenClaimsValidator('https://id.example.org/application/o/crm', 'client-id'),
        );
        return [$service, $manager, $repository, $exchanger, $decoder];
    }

    /** @return array<string, mixed> */
    private function claims(string $nonce): array
    {
        return [
            'sub' => 'external-subject',
            'iss' => 'https://id.example.org/application/o/crm',
            'aud' => 'client-id',
            'nonce' => $nonce,
            'iat' => 1700000000,
            'exp' => 1700000300,
        ];
    }
}
