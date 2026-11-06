<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Identity;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Identity\ExistingAccountLookup;
use Civi\OidcSso\Identity\ExistingIdentityLinker;
use Civi\OidcSso\Identity\ExternalIdentity;
use Civi\OidcSso\Identity\ExternalIdentityRepository;
use Civi\OidcSso\Identity\ProvisioningTransaction;
use PHPUnit\Framework\TestCase;

final class ExistingIdentityLinkerTest extends TestCase
{
    public function testLinksSubjectToExistingUserWithoutChangingProfile(): void
    {
        $repository = $this->createMock(ExternalIdentityRepository::class);
        $accounts = $this->createMock(ExistingAccountLookup::class);
        $identity = new ExternalIdentity(10, 20, 20);
        $repository->expects(self::once())->method('find')->willReturn(null);
        $accounts->expects(self::once())->method('byUserId')->with(20)->willReturn($identity);
        $repository->expects(self::once())->method('create')
            ->with('https://id.example.org', 'subject-1', $identity);

        self::assertSame(
            $identity,
            (new ExistingIdentityLinker($repository, $accounts, $this->transaction()))
                ->link('https://id.example.org', 'subject-1', 20),
        );
    }

    public function testRefusesToReplaceExistingLink(): void
    {
        $repository = $this->createMock(ExternalIdentityRepository::class);
        $accounts = $this->createMock(ExistingAccountLookup::class);
        $repository->method('find')->willReturn(new ExternalIdentity(10, 20, 20));
        $accounts->expects(self::never())->method('byUserId');

        $this->expectException(OidcValidationException::class);
        (new ExistingIdentityLinker($repository, $accounts, $this->transaction()))
            ->link('https://id.example.org', 'subject-1', 20);
    }

    private function transaction(): ProvisioningTransaction
    {
        return new class implements ProvisioningTransaction {
            public function run(callable $operation): mixed
            {
                return $operation();
            }
        };
    }
}
