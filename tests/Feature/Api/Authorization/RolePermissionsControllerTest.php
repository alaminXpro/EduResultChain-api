<?php

namespace Tests\Feature\Api\Authorization;

use Facades\Tests\Setup\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Tests\Feature\ApiTestCase;
use Vanguard\Http\Resources\PermissionResource;
use Vanguard\Permission;
use Vanguard\Role;
use Vanguard\User;

class RolePermissionsControllerTest extends ApiTestCase
{
    /** @test */
    public function unauthenticated()
    {
        $role = Role::factory()->create();

        $this->getJson("/api/roles/{$role->id}/permissions")
            ->assertStatus(401);
    }

    /** @test */
    public function get_settings_without_permission()
    {
        $role = Role::factory()->create();

        $user = User::factory()->create();

        $this->actingAs($user, self::API_GUARD)
            ->getJson("/api/roles/{$role->id}/permissions")
            ->assertStatus(403);
    }

    /** @test */
    public function get_role_permissions()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->attachPermission($permission);

        $this->actingAs($this->getUser(), self::API_GUARD)
            ->getJson("/api/roles/{$role->id}/permissions")
            ->assertOk()
            ->assertJsonFragment(
                PermissionResource::collection([$permission])->toArray(request())
            );
    }

    /** @test */
    public function update_role_permissions()
    {
        $role = Role::factory()->create();
        $permissions1 = Permission::factory()->times(2)->create();
        $permissions2 = Permission::factory()->times(3)->create();

        $role->attachPermissions($permissions1->toArray());

        $this->actingAs($this->getUser(), self::API_GUARD)
            ->putJson("/api/roles/{$role->id}/permissions", [
                'permissions' => $permissions2->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJsonFragment(
                (new PermissionResource($permissions2[0]))->toArray(
                    $this->createTestRequest(Request::createFromGlobals())
                )
            )
            ->assertJsonFragment(
                (new PermissionResource($permissions2[1]))->toArray(
                    $this->createTestRequest(Request::createFromGlobals())
                )
            )
            ->assertJsonFragment(
                (new PermissionResource($permissions2[2]))->toArray(
                    $this->createTestRequest(Request::createFromGlobals())
                )
            );

        foreach ($permissions2 as $perm) {
            $this->assertDatabaseHas('permission_role', [
                'permission_id' => $perm->id,
                'role_id' => $role->id,
            ]);
        }
    }

    /**
     * @return mixed
     */
    private function getUser()
    {
        return UserFactory::user()->withPermissions('permissions.manage')->create();
    }
}
