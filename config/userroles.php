<?php

return [

    /*
     * The Default Values set during the Database Migration Seeding of UserRole and Permissions
     */
    'default' => [

        /*
         * The default Roles set during the Database Migration Seeding of UserRole
         *
         * Index Key = Unique code given to the User Role (eg. 'admin').
         *
         * Values Inside Element Array:-
         * code = Unique code given to the User Role (eg. 'admin').
         * display_name = The name to be displayed in the View.
         * description = A brief description on what the purpose or task of the User Role.
         * is_active = Set the Active status of the User Role ( 1 => Active, 0 => Inactive)
         *
         */
        'roles' => [],

        /*
         * A default Administrator for the System.
         *
         * name = Name of the Administrator.
         * email = EMail of the Administrator.
         * password = Password for the system account.
         *
         * All these can be set in ENV file with variables as follows:-
         * name = DEFAULT_ADMIN_NAME
         * email = DEFAULT_ADMIN_EMAIL
         * password = DEFAULT_ADMIN_PASSWORD
         *
         */
        'admin_user' => [
            'name' => env('DEFAULT_ADMIN_NAME', 'Administrator'),
            'email' => env('DEFAULT_ADMIN_EMAIL', 'admin@example.com'),
            'password' => env('DEFAULT_ADMIN_PASSWORD', 'password')
        ],

        /*
         * The default Roles set during the Database Migration Seeding of UserRolePermissions
         *
         * Index Key = Unique code given to the User Role Permission (eg. 'users.view').
         *
         * Values Inside Element Array:-
         * code = Unique code given to the Permission (eg. 'users.view').
         * display_name = The short self-descriptive name.
         * description = A brief description on what the purpose of the Permission.
         * is_active = Set the Active status of the Permission ( 1 => Active, 0 => Inactive)
         *
         */
        'permissions' => [],

    ],

    /*
     * List of Database Migration Seeders to be run in the order.
     *
     */
    'seeders' => [
        \Modules\Supervisor\Database\Seeders\GenerateSupervisorRoleSeeder::class,
        \Modules\Picker\Database\Seeders\GeneratePickerRoleSeeder::class,
        \Modules\Driver\Database\Seeders\GenerateDriverRoleSeeder::class,
        \Modules\Cashier\Database\Seeders\GenerateCashierRoleSeeder::class,
    ],

];
