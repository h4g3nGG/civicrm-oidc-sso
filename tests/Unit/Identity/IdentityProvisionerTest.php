<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Identity;

use Civi\OidcSso\Identity\ClaimsProfileExtractor;
use Civi\OidcSso\Identity\ExternalIdentity;
use Civi\OidcSso\Identity\ExternalIdentityRepository;
use Civi\OidcSso\Identity\IdentityProvisioner;
use Civi\OidcSso\Identity\LocalAccountGateway;
use Civi\OidcSso\Identity\ProvisioningTransaction;
use Civi\OidcSso\Identity\UserProfile;
use Civi\OidcSso\Audit\AuditEvent;
use Civi\OidcSso\Audit\AuditEventWriter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class IdentityProvisionerTest extends TestCase
{
    public function testCreatesAccountAndStableLinkForNewSubject(): void
    {
        $identities = $this->createMock(ExternalIdentityRepository::class);
        $accounts = $this->createMock(LocalAccountGateway::class);
        $created = new ExternalIdentity(10, 20, 20);
        $identities->expects(self::once())->method('find')
            ->with('https://id.example.org', 'subject-1')->willReturn(null);
        $accounts->expects(self::once())->method('create')
            ->with(self::callback(static fn(UserProfile $profile): bool => $profile->email === 'person@example.org'))
            ->willReturn($created);
        $identities->expects(self::once())->method('create')
            ->with('https://id.example.org', 'subject-1', $created);

        $audit = $this->createMock(AuditEventWriter::class);
        $audit->expects(self::once())->method('append')
            ->with(AuditEvent::IDENTITY_CREATED, AuditEvent::SUCCESS, 20);

        self::assertSame($created, $this->provisioner($identities, $accounts, $audit)->provision($this->claims()));
    }

    public function testUpdatesOnlyAccountAlreadyLinkedByIssuerAndSubject(): void
    {
        $identities = $this->createMock(ExternalIdentityRepository::class);
        $accounts = $this->createMock(LocalAccountGateway::class);
        $existing = new ExternalIdentity(10, 20, 20);
        $identities->expects(self::once())->method('find')->willReturn($existing);
        $accounts->expects(self::never())->method('create');
        $identities->expects(self::never())->method('create');
        $accounts->expects(self::once())->method('update')->with($existing, self::isInstanceOf(UserProfile::class));

        self::assertSame($existing, $this->provisioner($identities, $accounts)->provision($this->claims()));
    }

    /** @param ExternalIdentityRepository&MockObject $identities @param LocalAccountGateway&MockObject $accounts */
    private function provisioner(
        ExternalIdentityRepository $identities,
        LocalAccountGateway $accounts,
        ?AuditEventWriter $audit = null,
    ): IdentityProvisioner {
        $transaction = new class implements ProvisioningTransaction {
            public function run(callable $operation): mixed
            {
                return $operation();
            }
        };
        return new IdentityProvisioner($identities, $accounts, new ClaimsProfileExtractor(), $transaction, $audit);
    }

    /** @return array<string, mixed> */
    private function claims(): array
    {
        return [
            'iss' => 'https://id.example.org',
            'sub' => 'subject-1',
            'email' => 'person@example.org',
            'email_verified' => true,
            'preferred_username' => 'person.name',
            'name' => 'Person Name',
            'given_name' => 'Person',
            'family_name' => 'Name',
        ];
    }
}
