<?php

use RZP\Http\RequestHeader;

/**
 * Throttle specific local settings.
 */
return [
    'skip' => env('THROTTLE_SKIP', false),

    /*
    |
    | Map of (1) route and (2) route, request(query or post) and header parameters, value of which will be appended in
    | default throttle identifier. This is optional configuration which is used in various cases as listed below.
    |
    | Example:
    | 'sample_api_route_name' => [
    |     'route_params'   => ['param1', 'param2'],
    |     'request_params' => ['query1', 'query2'],
    |     // ...
    | ]
    |
    | For above route, the throttle key have values of above listed route & query parameters appended
    | Hence, final identifier against which throttling will happen might look like below:
    | "pv:sample_api_route_name:test:public:0::::192.168.10.1:param1_value:param2_value:query1_value:query2_value"
    |
    */
    'throttle_key' => [
        'invoice_send_notification' => [
            'route_params' => ['x_entity_id'],
        ],
        'invoice_get_pdf' => [
            'route_params' => ['x_entity_id'],
        ],
        'user_change_password' => [
            'header_params' => [RequestHeader::X_DASHBOARD_USER_ID],
        ],
        'admin_forgot_password' => [
            'request_params' => ['email'],
        ],
        'admin_reset_password' => [
            'request_params' => ['email'],
        ],
        'user_confirm_by_data' => [
            'request_params' => ['email'],
        ],
        'user_resend_verification' => [
            'header_params' => [RequestHeader::X_DASHBOARD_USER_ID],
        ],
        'user_reset_password_create' => [
            'request_params' => ['email'],
        ],
        'user_reset_password_token' => [
            'request_params' => ['email'],
        ],
    ],
];
