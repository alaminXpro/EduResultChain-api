<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Vanguard\Permission;
use Vanguard\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminRole = Role::where('name', 'Admin')->first();

        $permissions[] = Permission::create([
            'name' => 'users.manage',
            'display_name' => 'Manage Users',
            'description' => 'Manage users and their sessions.',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'users.activity',
            'display_name' => 'View System Activity Log',
            'description' => 'View activity log for all system users.',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'roles.manage',
            'display_name' => 'Manage Roles',
            'description' => 'Manage system roles.',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'permissions.manage',
            'display_name' => 'Manage Permissions',
            'description' => 'Manage role permissions.',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'settings.general',
            'display_name' => 'Update General System Settings',
            'description' => '',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'settings.auth',
            'display_name' => 'Update Authentication Settings',
            'description' => 'Update authentication and registration system settings.',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'settings.notifications',
            'display_name' => 'Update Notifications Settings',
            'description' => '',
            'removable' => false,
        ]);

        $permissions[] = Permission::create([
            'name' => 'dashboard.view',
            'display_name' => 'View Dashboard',
            'description' => 'Access and view the admin dashboard',
            'removable' => false,
        ]);

        $adminRole->attachPermissions($permissions);

        $boardRole = Role::where('name', 'Board')->first();
        $boardPermissions = [];
        
        // Board-specific permissions
        $boardPermissions[] = Permission::create([
            'name' => 'exam.marks.manage',
            'display_name' => 'Manage Exam Marks',
            'description' => 'Enter and update subject-wise marks for students',
            'removable' => false,
        ]);

        $boardPermissions[] = Permission::create([
            'name' => 'results.publish',
            'display_name' => 'Publish Results',
            'description' => 'Review and publish computed results',
            'removable' => false,
        ]);

        $boardPermissions[] = Permission::create([
            'name' => 'results.unpublish',
            'display_name' => 'Unpublish Results',
            'description' => 'Unpublish previously published results',
            'removable' => false,
        ]);

        $boardPermissions[] = Permission::create([
            'name' => 'revalidation.manage',
            'display_name' => 'Manage Revalidation Requests',
            'description' => 'Review and process result revalidation requests',
            'removable' => false,
        ]);

        $boardPermissions[] = Permission::create([
            'name' => 'subjects.manage',
            'display_name' => 'Manage Subjects',
            'description' => 'Create, update, and delete subjects',
            'removable' => false,
        ]);

        $boardPermissions[] = Permission::create([
            'name' => 'results.statistics',
            'display_name' => 'View Result Statistics',
            'description' => 'Access and analyze result statistics',
            'removable' => false,
        ]);

        $institutionRole = Role::where('name', 'Institution')->first();
        $institutionPermissions = [];

        // Institution-specific permissions
        $institutionPermissions[] = Permission::create([
            'name' => 'students.manage',
            'display_name' => 'Manage Students',
            'description' => 'Register and manage student information',
            'removable' => false,
        ]);

        $institutionPermissions[] = Permission::create([
            'name' => 'form.fillup.manage',
            'display_name' => 'Manage Form Fillups',
            'description' => 'Register students for exams',
            'removable' => false,
        ]);

        $institutionPermissions[] = Permission::create([
            'name' => 'results.view',
            'display_name' => 'View Results',
            'description' => 'View published results for institution students',
            'removable' => false,
        ]);

        $institutionPermissions[] = Permission::create([
            'name' => 'institution.statistics',
            'display_name' => 'View Institution Statistics',
            'description' => 'Access and analyze institution-specific statistics',
            'removable' => false,
        ]);

        $institutionPermissions[] = Permission::create([
            'name' => 'revalidation.request',
            'display_name' => 'Request Result Revalidation',
            'description' => 'Submit revalidation requests for student results',
            'removable' => false,
        ]);

        // Attach permissions to roles
        $boardRole->attachPermissions($boardPermissions);
        $institutionRole->attachPermissions($institutionPermissions);
    }
}
