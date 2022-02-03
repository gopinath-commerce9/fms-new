<?php

namespace Modules\UserRole\Database\Seeders;

use Illuminate\Database\Seeder;
use Schema;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\Permission;
use Modules\UserRole\Entities\PermissionMap;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $availablePermissions = $this->getDefaultPermissions();
        $mainPermissionList = array_keys($availablePermissions);

        $permissionList = config('userroles.default.permissions');
        $requiredPermissionFields = ['code', 'display_name', 'description', 'is_active'];

        if (isset($permissionList) && is_array($permissionList) && (count($permissionList) > 0)) {
            foreach ($permissionList as $permissionKey => $permissionEl) {
                $cleanPermissionKey = strtolower(str_replace(' ', '.', trim($permissionKey)));
                if (array_key_exists($cleanPermissionKey, $availablePermissions)) {
                    continue;
                }
                $canAddPermission = false;
                $tempPermission = [];
                if (is_array($permissionEl) && (count($permissionEl) > 0)) {
                    $canAddPermission = true;
                    foreach ($requiredPermissionFields as $reqField) {
                        if (array_key_exists($reqField, $permissionEl) && (trim($permissionEl[$reqField])) != '') {
                            $cleanFieldValue = strtolower(str_replace(' ', '.', trim($permissionEl[$reqField])));
                            $tempPermission[$reqField] = ($reqField == 'code') ? $cleanFieldValue : $permissionEl[$reqField];
                        } else {
                            $canAddPermission = false;
                        }
                    }
                }
                if ($canAddPermission) {
                    $availablePermissions[$cleanPermissionKey] = $tempPermission;
                }
            }
        }

        if (count($availablePermissions) > 0) {
            /* Schema::disableForeignKeyConstraints();
            Permission::query()->truncate();*/
            foreach ($availablePermissions as $permissionEl) {
                $insertedUserPermission = Permission::firstOrCreate([
                    'code' => $permissionEl['code']
                ], [
                    'display_name' => $permissionEl['display_name'],
                    'description' => $permissionEl['description'],
                    'is_active' => $permissionEl['is_active']
                ]);
            }
            /*Schema::enableForeignKeyConstraints();*/
            $this->command->info('Seeded the Default User Permissions!');
        }

        $adminRole = UserRole::firstWhere('code', 'admin');
        if ($adminRole && is_array($mainPermissionList) && (count($mainPermissionList) > 0)) {
            $mainPermissionCollection = Permission::whereIn('code', $mainPermissionList)->get();
            $permissionMapList = [];
            foreach ($mainPermissionCollection as $permissionEl) {
                $permissionMapList[] = [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permissionEl->id,
                    'permitted' => 1,
                    'is_active' => 1
                ];
            }
            if (count($permissionMapList) > 0) {
                foreach ($permissionMapList as $permissionEl) {
                    $insertedUserPermissionMap = PermissionMap::updateOrCreate([
                        'role_id' => $permissionEl['role_id'],
                        'permission_id' => $permissionEl['permission_id'],
                    ], [
                        'permitted' => $permissionEl['permitted'],
                        'is_active' => $permissionEl['is_active']
                    ]);
                }
                $this->command->info('Seeded the Default User Permission Maps!');
            }

        }

    }

    /**
     * Returns default fixed User Role Permissions which must be present for a accessing the system.
     *
     * @return array
     */
    private function getDefaultPermissions() {
        return [
            'users.view' => [
                'code' => 'users.view',
                'display_name' => 'User View',
                'description' => 'Permission to view the User details.',
                'is_active' => 1,
            ],
            'users.create' => [
                'code' => 'users.create',
                'display_name' => 'User Create',
                'description' => 'Permission to create the User.',
                'is_active' => 1,
            ],
            'users.update' => [
                'code' => 'users.update',
                'display_name' => 'User Update',
                'description' => 'Permission to update the User details.',
                'is_active' => 1,
            ],
            'users.delete' => [
                'code' => 'users.delete',
                'display_name' => 'User Delete',
                'description' => 'Permission to delete the User.',
                'is_active' => 1,
            ],
            'user-roles.view' => [
                'code' => 'user-roles.view',
                'display_name' => 'User Role View',
                'description' => 'Permission to view the User Role details.',
                'is_active' => 1,
            ],
            'user-roles.create' => [
                'code' => 'user-roles.create',
                'display_name' => 'User Role Create',
                'description' => 'Permission to create the User Role.',
                'is_active' => 1,
            ],
            'user-roles.update' => [
                'code' => 'user-roles.update',
                'display_name' => 'User Role Update',
                'description' => 'Permission to update the User Role details.',
                'is_active' => 1,
            ],
            'user-roles.assign' => [
                'code' => 'user-roles.assign',
                'display_name' => 'User Role Assign',
                'description' => 'Permission to assign the Role to the User.',
                'is_active' => 1,
            ],
            'user-roles.delete' => [
                'code' => 'user-roles.delete',
                'display_name' => 'User Role Delete',
                'description' => 'Permission to delete the User Role.',
                'is_active' => 1,
            ],
            'user-role-permissions.view' => [
                'code' => 'user-role-permissions.view',
                'display_name' => 'User Role Permissions View',
                'description' => 'Permission to view the User Permission details.',
                'is_active' => 1,
            ],
            'user-role-permissions.create' => [
                'code' => 'user-role-permissions.create',
                'display_name' => 'User Role Permissions Create',
                'description' => 'Permission to create the User Permission.',
                'is_active' => 1,
            ],
            'user-role-permissions.update' => [
                'code' => 'user-role-permissions.update',
                'display_name' => 'User Role Permissions Update',
                'description' => 'Permission to update the User Permission details.',
                'is_active' => 1,
            ],
            'user-role-permissions.grant' => [
                'code' => 'user-role-permissions.grant',
                'display_name' => 'User Role Permissions Grant',
                'description' => 'Permission to grant the permissions to the User Role.',
                'is_active' => 1,
            ],
            'user-role-permissions.delete' => [
                'code' => 'user-role-permissions.delete',
                'display_name' => 'User Role Permissions Delete',
                'description' => 'Permission to delete the User Permission.',
                'is_active' => 1,
            ]
        ];
    }
}
