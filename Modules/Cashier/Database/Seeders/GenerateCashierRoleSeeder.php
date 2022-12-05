<?php

namespace Modules\Cashier\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\UserRole\Entities\UserRole;

class GenerateCashierRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Model::unguard();

        $cashierData = $this->getCashierRoleData();
        $insertData = [];
        $requiredRoleFields = ['code', 'display_name', 'description', 'is_active'];
        if (isset($cashierData) && is_array($cashierData) && (count($cashierData) > 0)) {
            foreach ($cashierData as $roleKey => $roleEl) {
                $cleanRoleKey = strtolower(str_replace(' ', '_', trim($roleKey)));
                if (array_key_exists($cleanRoleKey, $insertData)) {
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
                    $insertData[$cleanRoleKey] = $tempRole;
                }
            }
        }

        if (count($insertData) > 0) {
            foreach ($insertData as $roleEl) {
                $insertedUserRole = UserRole::firstOrCreate([
                    'code' => $roleEl['code']
                ], [
                    'display_name' => $roleEl['display_name'],
                    'description' => $roleEl['description'],
                    'is_active' => $roleEl['is_active']
                ]);
            }
            $this->command->info('Seeded the User Roles set in the Cashier Module!');
        }

    }

    private function getCashierRoleData() {

        return [
            'cashier' => [
                'code' => 'cashier',
                'display_name' => 'Cashier',
                'description' => 'The User who processes the POS System and Sale Order Item Scanning process.',
                'is_active' => 1,
            ],
        ];

    }

}
