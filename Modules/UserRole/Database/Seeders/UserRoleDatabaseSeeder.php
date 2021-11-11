<?php

namespace Modules\UserRole\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class UserRoleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");

        $this->call(UserRoleSeeder::class);
        $this->command->info('Seeded the Default User Roles!');

        $this->call(PermissionSeeder::class);
        $this->command->info('Seeded the Default User Permissions!');
    }
}
