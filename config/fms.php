<?php

return [

    'defaults' => [
        'apiEnv' => env('DEFAULT_API_ENV', 'production'),
    ],

    'api' => [

        'development' => [

            'defaults' => [
                'channel' => 'gb-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'gb-1' => [
                    'id' => 'gb-1',
                    'name' => 'Magento UAT',
                    'url' => 'https://admuat.goodbasket.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'ajaygb',
                    'authSecret' => 'jayaraj321$A',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

        'testing' => [

            'defaults' => [
                'channel' => 'gb-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'gb-1' => [
                    'id' => 'gb-1',
                    'name' => 'Magento UAT',
                    'url' => 'https://admuat.goodbasket.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'ajaygb',
                    'authSecret' => 'jayaraj321$A',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

        'staging' => [

            'defaults' => [
                'channel' => 'gb-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'gb-1' => [
                    'id' => 'gb-1',
                    'name' => 'Magento UAT',
                    'url' => 'https://admuat.goodbasket.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'nived',
                    'authSecret' => 'Commerce@9',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

        'production' => [

            'defaults' => [
                'channel' => 'gb-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'gb-1' => [
                    'id' => 'gb-1',
                    'name' => 'Magento',
                    'url' => 'https://api.goodbasket.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'nived',
                    'authSecret' => 'Commerce@9',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

    ],

    'order_statuses' => [
        'being_prepared' => 'Being Prepared',
        'canceled' => 'Canceled',
        'closed' => 'Closed',
        'complete' => 'Complete',
        'fraud' => 'Suspected Fraud',
        'holded' => 'On Hold',
        'order_updated' => 'Order Updated',
        'out_for_delivery' => 'Out For Delivery',
        'ready_to_dispatch' => 'Ready To Dispatch',
        'payment_review'  => 'Payment Review',
        'pending'  => 'Pending',
        'pending_payment' => 'Pending Payment',
        'processing' => 'Processing',
        'returned' => 'Returned',
        'delivered' => 'Delivered',
    ],

    'emirates' => [
        'DXB' =>'Dubai',
        'SHJ' =>'Sharjah',
        'AUH'=>'Abu Dhabhi',
        'AJM' => 'Ajman'
    ],

    'role_allowed_statuses' => [
        'supervisor' => [
            'pending',
            'processing',
            'being_prepared',
            'holded',
            'order_updated',
            'ready_to_dispatch',
            'out_for_delivery',
            'delivered',
            'canceled',
        ],
        'picker' => [
            'being_prepared',
        ],
        'driver' => [
            'ready_to_dispatch',
            'out_for_delivery',
        ],
    ],

    'delivery_time_slots' => [
        '10:00 AM - 4:00 PM',
        '4:00 PM - 10:00 PM',
        '1:00 PM - 7:00 PM',
    ],

    'pos_system' => [

        'order_sources' => [
            'ELGROCER' => [
                'code' => 'ELGROCER',
                'source' => 'ELGROCER',
                'channelId' => '4',
                'charge' => '5.00',
                'email' => 'elgrocer@commerce9.io',
                'contact' => '+97155555555'
            ],
            'INSTORE' => [
                'code' => 'INSTORE',
                'source' => 'InStore',
                'channelId' => '5',
                'charge' => '0.00',
                'email' => 'instore@commerce9.io',
                'contact' => '+97155555555'
            ],
            'INSTASHOP' => [
                'code' => 'INSTASHOP',
                'source' => 'InstaShop',
                'channelId' => '6',
                'charge' => '5.00',
                'email' => 'instashop@commerce9.io',
                'contact' => '+97155555555'
            ],
        ],

        'payment_methods' => [
            'cashondelivery' => [
                'method' => 'cashondelivery',
                'title' => 'Cash On Delivery'
            ],
            'banktransfer' => [
                'method' => 'banktransfer',
                'title' => 'Credit Card On Delivery'
            ],
        ],

    ],

    'fulfillment' => [
        'done_by' => 'Commerce9'
    ],

];
