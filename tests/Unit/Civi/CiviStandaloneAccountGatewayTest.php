<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Civi;

use Civi\OidcSso\Civi\CiviStandaloneAccountGateway;
use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Identity\ExternalIdentity;
use Civi\OidcSso\Identity\UserProfile;
use PHPUnit\Framework\TestCase;

final class CiviStandaloneAccountGatewayTest extends TestCase
{
    public function testCreatesContactEmailAndStandaloneUserThroughApi(): void
    {
        $api = new FakeApi4Client();
        $api->queue('Contact', 'create', [['id' => 10]]);
        $api->queue('Email', 'create', [['id' => 11]]);
        $api->queue('User', 'create', [['id' => 20]]);

        $identity = (new CiviStandaloneAccountGateway($api))->create($this->profile());

        self::assertEquals(new ExternalIdentity(10, 20, 20), $identity);
        self::assertSame(
            ['User.get', 'User.get', 'Email.get', 'Contact.create', 'Email.create', 'User.create'],
            array_map(static fn(array $call): string => $call['entity'] . '.' . $call['action'], $api->calls),
        );
        $userValues = $api->calls[5]['parameters']['values'];
        self::assertSame('person.name', $userValues['username']);
        self::assertSame('person@example.org', $userValues['uf_name']);
        self::assertSame(10, $userValues['contact_id']);
        self::assertIsString($userValues['password']);
        self::assertGreaterThanOrEqual(64, strlen($userValues['password']));
    }

    public function testRefusesToLinkExistingEmailImplicitly(): void
    {
        $api = new FakeApi4Client();
        $api->queue('Email', 'get', [['id' => 99]]);

        $this->expectException(OidcValidationException::class);
        (new CiviStandaloneAccountGateway($api))->create($this->profile());
    }

    public function testUpdatesOnlyTheExplicitlyLinkedAccount(): void
    {
        $api = new FakeApi4Client();
        $api->queue('Email', 'get', []);
        $api->queue('Email', 'get', [['id' => 11]]);

        (new CiviStandaloneAccountGateway($api))->update(new ExternalIdentity(10, 20, 20), $this->profile());

        self::assertSame(
            ['User.get', 'User.get', 'Email.get', 'Contact.update', 'Email.get', 'Email.update', 'User.update'],
            array_map(static fn(array $call): string => $call['entity'] . '.' . $call['action'], $api->calls),
        );
        self::assertSame([['id', '=', 20]], $api->calls[6]['parameters']['where']);
    }

    private function profile(): UserProfile
    {
        return new UserProfile(
            'person@example.org',
            'person.name',
            'Person Name',
            'Person',
            'Name',
        );
    }
}
