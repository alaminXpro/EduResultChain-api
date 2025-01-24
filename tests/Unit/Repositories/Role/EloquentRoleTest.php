<?php

namespace Tests\Unit\Repositories\Role;

use Event;
use Facades\Tests\Setup\RoleFactory;
use Facades\Tests\Setup\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Vanguard\Events\Role\Created;
use Vanguard\Repositories\Role\EloquentRole;
use Vanguard\Role;

class EloquentRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var EloquentRole
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(EloquentRole::class);
    }

    /** @test */
    public function all()
    {
        $roles = Role::factory()->times(4)->create();

        $this->assertEquals(
            $roles->toArray(),
            $this->repo->all()->toArray()
        );
    }

    /** @test */
    public function get_all_with_users_count()
    {
        $roleA = RoleFactory::create();
        $roleB = RoleFactory::create();
        $roleC = RoleFactory::create();

        UserFactory::role($roleA)->create();
        UserFactory::role($roleA)->create();
        UserFactory::role($roleB)->create();

        $roleA->users_count = 2;
        $roleB->users_count = 1;
        $roleC->users_count = 0;

        $this->assertEquals(
            [$roleA->toArray(), $roleB->toArray(), $roleC->toArray()],
            $this->repo->getAllWithUsersCount()->toArray()
        );
    }

    /** @test */
    public function create()
    {
        Event::fake([
            Created::class,
        ]);

        $data = ['name' => 'foo', 'display_name' => 'Foo'];
        $role = $this->repo->create($data);

        $this->assertDatabaseHas('roles', $data + ['id' => $role->id]);

        Event::assertDispatched(Created::class);
    }

    /** @test */
    public function update()
    {
        Event::fake([
            \Vanguard\Events\Role\Updated::class,
        ]);

        $role = Role::factory()->create();

        $data = ['name' => 'foo', 'display_name' => 'Foo'];

        $this->repo->update($role->id, $data);

        $this->assertDatabaseHas('roles', $data + ['id' => $role->id]);

        Event::assertDispatched(\Vanguard\Events\Role\Updated::class);
    }

    /** @test */
    public function delete_role()
    {
        Event::fake([
            \Vanguard\Events\Role\Deleted::class,
        ]);

        $role = Role::factory()->create();

        $this->repo->delete($role->id);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        Event::assertDispatched(\Vanguard\Events\Role\Deleted::class);
    }

    /** @test */
    public function updatePermissions()
    {
        $role = Role::factory()->create();
        $permissions = \Vanguard\Permission::factory()->times(2)->create();

        $this->repo->updatePermissions($role->id, $permissions->pluck('id')->toArray());

        $this->assertDatabaseHas('permission_role', ['role_id' => $role->id, 'permission_id' => $permissions[0]->id]);
        $this->assertDatabaseHas('permission_role', ['role_id' => $role->id, 'permission_id' => $permissions[1]->id]);
    }

    /** @test */
    public function lists()
    {
        $roles = Role::factory()->times(4)->create();
        $roles = $roles->pluck('display_name', 'id');

        $this->assertEquals($roles->toArray(), $this->repo->lists()->toArray());
    }
}
