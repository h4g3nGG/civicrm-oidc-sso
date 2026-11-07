<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Civi;

use Civi\OidcSso\Civi\RoleMappingSetting;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class RoleMappingSettingTest extends TestCase
{
    public function testDecodesJsonSettingValue(): void
    {
        self::assertSame(['group.staff' => [3]], RoleMappingSetting::decode('{"group.staff":[3]}'));
    }

    public function testAcceptsArrayReturnedByCiviSettingsApi(): void
    {
        self::assertSame(
            ['group.staff' => [3], 'group.admin' => [2]],
            RoleMappingSetting::decode(['group.staff' => [3], 'group.admin' => [2]]),
        );
    }

    public function testCanonicalizesApiArrayForTextFormWithoutChangingMapping(): void
    {
        self::assertSame(
            '{"group.staff":[3],"group.admin":[2]}',
            RoleMappingSetting::canonicalJson(['group.staff' => [3], 'group.admin' => [2]]),
        );
        self::assertSame('{}', RoleMappingSetting::canonicalJson([]));
    }

    public function testRejectsJsonListEvenWhenEmpty(): void
    {
        $this->expectException(UnexpectedValueException::class);
        RoleMappingSetting::decode('[]');
    }

    public function testRejectsInvalidRoleIds(): void
    {
        $this->expectException(UnexpectedValueException::class);
        RoleMappingSetting::decode(['group.staff' => ['3']]);
    }
}
