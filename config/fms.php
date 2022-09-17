<?php

return [

    'defaults' => [
        'apiEnv' => env('DEFAULT_API_ENV', 'production'),
    ],

    'api' => [

        'development' => [

            'defaults' => [
                'channel' => 'ac-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'ac-1' => [
                    'id' => 'ac-1',
                    'name' => 'Magento UAT',
                    'url' => 'https://staging.aanacart.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'accessToken' => '6cxvsesia7flvsdmu9gn3z0uoaqfz2i2',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'gopicommerce',
                    'authSecret' => 'commerce9@c9',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

        'testing' => [

            'defaults' => [
                'channel' => 'ac-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'ac-1' => [
                    'id' => 'ac-1',
                    'name' => 'Magento UAT',
                    'url' => 'https://staging.aanacart.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'accessToken' => '6cxvsesia7flvsdmu9gn3z0uoaqfz2i2',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'gopicommerce',
                    'authSecret' => 'commerce9@c9',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

        'staging' => [

            'defaults' => [
                'channel' => 'ac-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'ac-1' => [
                    'id' => 'ac-1',
                    'name' => 'Magento UAT',
                    'url' => 'https://staging.aanacart.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'accessToken' => '6cxvsesia7flvsdmu9gn3z0uoaqfz2i2',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'gopicommerce',
                    'authSecret' => 'commerce9@c9',
                    'timezone' => 'Asia/Dubai',
                    'timeoutSeconds' => 600,
                    'retryLoop' => 5,
                    'retryLoopInterval' => 60,
                ],
            ],

        ],

        'production' => [

            'defaults' => [
                'channel' => 'ac-1',
                'country_code' => 'AE'
            ],

            'channels' => [
                'ac-1' => [
                    'id' => 'ac-1',
                    'name' => 'Magento',
                    'url' => 'https://aanacart.com/',
                    'version' => 1,
                    'apiUri' => 'rest/V1/',
                    'accessToken' => '6cxvsesia7flvsdmu9gn3z0uoaqfz2i2',
                    'authUri' => 'integration/admin/token',
                    'authRole' => 'admin',
                    'authKey' => 'oms',
                    'authSecret' => 'AanaOMS#123',
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
        'shipped' => 'Shipped',
        'ngenius_pending' => 'N-Genius Online Pending',
        'ngenius_processing' => 'N-Genius Online Processing',
        'ngenius_complete' => 'N-Genius Online Complete',
        'ngenius_partially_refunded' => 'N-Genius Online Partially Refunded',
        'ngenius_partially_captured' => 'N-Genius Online Partially Captured',
        'ngenius_fully_refunded' => 'N-Genius Online Fully Refunded',
        'ngenius_fully_captured' => 'N-Genius Online Fully Captured',
        'ngenius_failed' => 'N-Genius Online Failed',
        'ngenius_auth_reversed' => 'N-Genius Online Auth Reversed',
        'ngenius_authorised' => 'N-Genius Online Authorised',
    ],

    /*'emirates' => [
        'DXB' =>'Dubai',
        'SHJ' =>'Sharjah',
        'AUH'=>'Abu Dhabhi',
        'AJM' => 'Ajman'
    ],*/

    'picklist_statuses' => [
        'pending',
        'processing',
        'ngenius_complete',
        'being_prepared',
        'holded',
        'order_updated'
    ],

    'resync_statuses' => [
        'pending',
        'pending_payment',
        'ngenius_pending',
        'processing',
        'ngenius_processing',
        'ngenius_complete',
        'being_prepared',
        'holded',
        'order_updated'
    ],

    'role_allowed_statuses' => [
        'supervisor' => [
            'pending',
            'ngenius_pending',
            'processing',
            'ngenius_processing',
            'ngenius_complete',
            'being_prepared',
            'holded',
            'order_updated',
            'ready_to_dispatch',
            'out_for_delivery',
            'delivered',
            'canceled',
            'closed',
        ],
        'picker' => [
            'being_prepared',
            'holded',
            'ready_to_dispatch',
        ],
        'driver' => [
            'ready_to_dispatch',
            'out_for_delivery',
            'delivered',
            'canceled',
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
        'done_by' => 'Aanacart'
    ],

    'company_info' => [
        'name' => 'Aanacart',
        'location' => 'Dubai, UAE',
        'website' => 'www.aanacart.com',
        'support' => 'info@aanacart.com',
        'contact' => '(+971) 50 150 5873'
    ],

    'kerabiya' => [
        'development' => [
            'url' => 'https://delivery.tsssmart.com/webservice/',
            'api_key' => 'baae33c9936521d27ca2d734fa948b59',
            'company_code' => 'AZ1198',
            'weight' => '10.00',
            'currency_code' => 'AED',
            'branch_name' => 'Dubai'
        ],
        'testing' => [
            'url' => 'https://delivery.tsssmart.com/webservice/',
            'api_key' => 'baae33c9936521d27ca2d734fa948b59',
            'company_code' => 'AZ1198',
            'weight' => '10.00',
            'currency_code' => 'AED',
            'branch_name' => 'Dubai'
        ],
        'staging' => [
            'url' => 'https://delivery.tsssmart.com/webservice/',
            'api_key' => 'baae33c9936521d27ca2d734fa948b59',
            'company_code' => 'AZ1198',
            'weight' => '10.00',
            'currency_code' => 'AED',
            'branch_name' => 'Dubai'
        ],
        'production' => [
            'url' => 'https://delivery.tsssmart.com/webservice/',
            'api_key' => 'baae33c9936521d27ca2d734fa948b59',
            'company_code' => 'AZ1198',
            'weight' => '10.00',
            'currency_code' => 'AED',
            'branch_name' => 'Dubai'
        ],
    ],

];
