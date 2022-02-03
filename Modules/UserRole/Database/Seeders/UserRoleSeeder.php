<?php

namespace Modules\UserRole\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Schema;
use App\Models\User;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $defaultAdmin = config('userroles.default.admin_user');
        $defaultAdminName = (is_array($defaultAdmin) && array_key_exists('name', $defaultAdmin)) ? $defaultAdmin['name'] : 'Administrator';
        $defaultAdminEmail = (is_array($defaultAdmin) && array_key_exists('email', $defaultAdmin)) ? $defaultAdmin['email'] : '';
        $defaultAdminPass = (is_array($defaultAdmin) && array_key_exists('password', $defaultAdmin)) ? $defaultAdmin['password'] : 'password';

        $defAdmin = null;
        if((trim($defaultAdminEmail) != '') && !User::firstWhere('email', trim($defaultAdminEmail)))
        {
            $defAdmin = new User();
            $defAdmin->name = trim($defaultAdminName);
            $defAdmin->email = trim($defaultAdminEmail);
            $defAdmin->password = Hash::make($defaultAdminPass);
            $defAdmin->saveQuietly();
        }

        $availableRoles = $this->getDefaultRoles();
        $roleList = config('userroles.default.roles');
        $requiredRoleFields = ['code', 'display_name', 'description', 'is_active'];

        if (isset($roleList) && is_array($roleList) && (count($roleList) > 0)) {
            foreach ($roleList as $roleKey => $roleEl) {
                $cleanRoleKey = strtolower(str_replace(' ', '_', trim($roleKey)));
                if (array_key_exists($cleanRoleKey, $availableRoles)) {
                    continue;
                }
                $canAddRole = false;
                $tempRole = [];
                if (is_array($roleEl) && (count($roleEl) > 0)) {
                    $canAddRole = true;
                    foreach ($requiredRoleFields as $reqField) {
                        if (array_key_exists($reqField, $roleEl) && (trim($roleEl[$reqField])) != '') {
                            $cleanFieldValue = strtolower(str_replace(' ', '_', trim($roleEl[$reqField])));
                            $tempRole[$reqField] = ($reqField == 'code') ? $cleanFieldValue : $roleEl[$reqField];
                        } else {
                            $canAddRole = false;
                        }
                    }
                }
                if ($canAddRole) {
                    $availableRoles[$cleanRoleKey] = $tempRole;
                }
            }
        }

        if (count($availableRoles) > 0) {
            /*Schema::disableForeignKeyConstraints();
            UserRole::query()->truncate();*/
            foreach ($availableRoles as $roleEl) {
                $insertedUserRole = UserRole::firstOrCreate([
                    'code' => $roleEl['code']
                ], [
                    'display_name' => $roleEl['display_name'],
                    'description' => $roleEl['description'],
                    'is_active' => $roleEl['is_active']
                ]);
            }
            /*Schema::enableForeignKeyConstraints();*/
            $this->command->info('Seeded the Default User Roles!');
        }

        if ($defAdmin && UserRole::firstWhere('code', 'admin')) {
            $admRole = UserRole::firstWhere('code', 'admin');
            $insertedUserRoleMap = UserRoleMap::updateOrCreate([
                'user_id' => $defAdmin->id,
                'role_id' => $admRole->id,
            ], [
                'is_active' => $roleEl['is_active']
            ]);
            $this->command->info('Seeded the Default User Role Maps!');
        }

    }

    /**
     * Returns default fixed User Roles which must be present for a accessing the system.
     *
     * @return array
     */
    private function getDefaultRoles() {
        return [
            'admin' => [
                'code' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'The main Super User who maintains and access the system.',
                'is_active' => 1
            ]
        ];
    }
}
