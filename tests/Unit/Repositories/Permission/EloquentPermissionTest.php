<?php

namespace Tests\Unit\Repositories\Permission;

use Cache;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Vanguard\Events\Permission\Created;
use Vanguard\Permission;
use Vanguard\Repositories\Permission\EloquentPermission;

class EloquentPermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var EloquentPermission
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(EloquentPermission::class);
    }

    /** @test */
    public function all()
    {
        $permissions = Permission::factory()->times(4)->create();

        $this->assertEquals($permissions->toArray(), $this->repo->all()->toArray());
    }

    /** @test */
    public function create_permission()
    {
        Event::fake([
            Created::class,
        ]);

        $data = $this->getPermissionStubData();

        $perm = $this->repo->create($data);

        $this->assertDatabaseHas('permissions', $data + ['id' => $perm->id]);

        Event::assertDispatched(Created::class);
    }

    /** @test */
    public function update_permission()
    {
        Event::fake([
            \Vanguard\Events\Permission\Updated::class,
        ]);

        Cache::put('foo', 'bar');

        $data = $this->getPermissionStubData();

        $perm = Permission::factory()->create();

        $this->repo->update($perm->id, $data);

        $this->assertDatabaseHas('permissions', $data + ['id' => $perm->id])
            ->assertNull(Cache::get('foo'));

        Event::assertDispatched(\Vanguard\Events\Permission\Updated::class);
    }

    /** @test */
    public function delete_permission()
    {
        Event::fake([
            \Vanguard\Events\Permission\Deleted::class,
        ]);

        Cache::put('foo', 'bar');

        $perm = Permission::factory()->create();

        $this->repo->delete($perm->id);

        $this->assertDatabaseMissing('permissions', ['id' => $perm->id])
            ->assertNull(Cache::get('foo'));

        Event::assertDispatched(\Vanguard\Events\Permission\Deleted::class);
    }

    private function getPermissionStubData(): array
    {
        return [
            'name' => Str::random(5),
            'display_name' => Str::random(5),
            'description' => 'foo',
            'removable' => true,
        ];
    }
}
