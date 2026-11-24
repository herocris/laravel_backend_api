<?php

namespace Tests\Unit\Support\Authorization;

use App\Support\Authorization\PermissionCatalog;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_returns_the_existing_catalog_in_its_original_creation_order(): void
    {
        $this->assertSame([
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',
            'permission.view', 'permission.create', 'permission.update', 'permission.delete',
            'drug.view', 'drug.create', 'drug.update', 'drug.delete',
            'drugPresentation.view', 'drugPresentation.create', 'drugPresentation.update', 'drugPresentation.delete',
            'weapon.view', 'weapon.create', 'weapon.update', 'weapon.delete',
            'ammunition.view', 'ammunition.create', 'ammunition.update', 'ammunition.delete',
            'confiscation.view', 'confiscation.create', 'confiscation.update', 'confiscation.delete',
            'drugConfiscation.view', 'drugConfiscation.create', 'drugConfiscation.update', 'drugConfiscation.delete',
            'weaponConfiscation.view', 'weaponConfiscation.create', 'weaponConfiscation.update', 'weaponConfiscation.delete',
            'ammunitionConfiscation.view', 'ammunitionConfiscation.create', 'ammunitionConfiscation.update', 'ammunitionConfiscation.delete',
            'dashboard.view', 'graph.view', 'map.view', 'activity.view',
        ], PermissionCatalog::names());
    }

    #[TestWith(['drugPresentation.update', true])]
    #[TestWith(['activity.view', true])]
    #[TestWith(['DrugPresentation.update', false])]
    #[TestWith(['external.view', false])]
    #[TestWith(['users.assign-roles', false])]
    #[TestWith(['confiscation.restore', false])]
    #[TestWith(['', false])]
    public function test_membership_uses_exact_catalog_names(string $name, bool $expected): void
    {
        $this->assertSame($expected, PermissionCatalog::contains($name));
    }
}
