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

        $seedersArray = [
            UserRoleSeeder::class,
            PermissionSeeder::class
        ];

        $otherSeederList = $this->getOtherSeederClasses();
        if (!is_null($otherSeederList) && is_array($otherSeederList) && (count($otherSeederList) > 0)) {
            foreach ($otherSeederList as $seederClass) {
                if (
                    !is_null($seederClass)
                    && is_string($seederClass)
                    && (trim($seederClass) != '')
                    && class_exists(trim($seederClass))
                    && is_subclass_of(trim($seederClass), Seeder::class)
                ) {
                    $seedersArray[] = $seederClass;
                }
            }
        }

        $this->call($seedersArray);

    }

    private function getOtherSeederClasses() {
        return config('userroles.seeders');
    }

}
