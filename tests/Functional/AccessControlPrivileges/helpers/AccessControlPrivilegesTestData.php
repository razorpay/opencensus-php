<?php

return [
    'testFetchAllPrivileges' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/cac/privileges',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'privilege_data' =>
                    array (
                        0 =>
                            array (
                                'name' => 'Bank Statement & Details',
                                'description' => 'Account statement & balance, banking details',
                                'label' => 'bankStatementAndDetails',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                    ),
                                'view_position' => 100,
                                'privilege_data' =>
                                    array (
                                        0 =>
                                            array (
                                                'name' => 'Account Statement & Balance',
                                                'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                                                'label' => 'accountStatement',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => '',
                                                                'description' => 'View and download account statement & balance',
                                                            ),
                                                    ),
                                                'view_position' => 200,
                                                'privilege_data' => NULL,
                                            ),
                                        1 =>
                                            array (
                                                'name' => 'Banking Details',
                                                'description' => 'Details of your virtual and current account (if applicable) in RazorpayX',
                                                'label' => 'bankingDetails',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => '',
                                                                'description' => 'View account number, IFSC code',
                                                            ),
                                                    ),
                                                'view_position' => 300,
                                                'privilege_data' => NULL,
                                            ),
                                    ),
                            ),
                        1 =>
                            array (
                                'name' => 'Payouts',
                                'description' => 'Single, bulk, tally payouts, payout links, payouts on invoices, contacts & fund accounts',
                                'label' => 'payouts',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                        'create' =>
                                            array (
                                                'label' => 'Create, Mark as Paid',
                                                'tooltip' => '',
                                                'description' => 'Create payouts, contacts & fund accounts, Mark invoices as paid',
                                            ),
                                        'view' =>
                                            array (
                                                'label' => 'View',
                                                'tooltip' => 'View by default on access to create, mark as paid',
                                                'description' => 'View and download payouts',
                                            ),
                                    ),
                                'view_position' => 400,
                                'privilege_data' => NULL,
                            ),
                        2 =>
                            array (
                                'name' => 'Invoices',
                                'description' => 'All invoices added to RazorpayX',
                                'label' => 'invoices',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                        'create' =>
                                            array (
                                                'label' => 'Create, Edit, Cancel',
                                                'tooltip' => '',
                                                'description' => 'Create, edit and cancel invoices',
                                            ),
                                        'view' =>
                                            array (
                                                'label' => 'View',
                                                'tooltip' => 'View by default on access to create, edit, cancel',
                                                'description' => 'View and download Invoices',
                                            ),
                                    ),
                                'view_position' => 500,
                                'privilege_data' => NULL,
                            ),
                        3 =>
                            array (
                                'name' => 'Tax Payments',
                                'description' => 'TDS, GST and Advance Tax Payments and tax challans',
                                'label' => 'taxPayments',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                        'create' =>
                                            array (
                                                'label' => 'Create, Mark as Paid',
                                                'tooltip' => '',
                                                'description' => 'Create and mark tax payments as paid',
                                            ),
                                        'view' =>
                                            array (
                                                'label' => 'View',
                                                'tooltip' => 'View by default on access to create, mark as paid',
                                                'description' => 'View and download tax payments',
                                            ),
                                    ),
                                'view_position' => 600,
                                'privilege_data' => NULL,
                            ),
                        4 =>
                            array (
                                'name' => 'Reporting and Insights',
                                'description' => 'All reports and additional analytics of your transactions',
                                'label' => 'reportingAndInsights',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                    ),
                                'view_position' => 700,
                                'privilege_data' =>
                                    array (
                                        0 =>
                                            array (
                                                'name' => 'Reports',
                                                'description' => 'Business specific reports, generic reports like account statement, payouts, invoices and tax payments.',
                                                'label' => 'reports',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => '',
                                                                'description' => 'View and download all reports',
                                                            ),
                                                    ),
                                                'view_position' => 800,
                                                'privilege_data' => NULL,
                                            ),
                                        1 =>
                                            array (
                                                'name' => 'Insights',
                                                'description' => 'Detailed analysis of transactions represented visually across all your accounts in RazorpayX',
                                                'label' => 'insights',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => '',
                                                                'description' => 'Slice and dice the detailed analysis of transactions across all your accounts in RazorpayX',
                                                            ),
                                                    ),
                                                'view_position' => 900,
                                                'privilege_data' => NULL,
                                            ),
                                    ),
                            ),
                        5 =>
                            array (
                                'name' => 'Account & Settings',
                                'description' => 'Of RazorpayX Account: Manage team & Workflow, Developer controls and Buiness Profile',
                                'label' => 'accountSettings',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                    ),
                                'view_position' => 1000,
                                'privilege_data' =>
                                    array (
                                        0 =>
                                            array (
                                                'name' => 'Manage Team',
                                                'description' => 'Manage team, roles and permissions',
                                                'label' => 'manageTeam',
                                                'actions' =>
                                                    array (
                                                        'create' =>
                                                            array (
                                                                'label' => 'Add, Edit',
                                                                'tooltip' => '',
                                                                'description' => 'Add team members, create, edit roles & permissions',
                                                            ),
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => 'View by default on access to add, edit',
                                                                'description' => 'View team members, roles & permissions',
                                                            ),
                                                    ),
                                                'view_position' => 1100,
                                                'privilege_data' => NULL,
                                            ),
                                        1 =>
                                            array (
                                                'name' => 'Developer Controls',
                                                'description' => 'API Keys and Webhooks',
                                                'label' => 'developerControls',
                                                'actions' =>
                                                    array (
                                                        'create' =>
                                                            array (
                                                                'label' => 'Edit',
                                                                'tooltip' => '',
                                                                'description' => 'Generate API Keys and Webhooks',
                                                            ),
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => 'View by default on access to edit',
                                                                'description' => 'View API Keys and Webhooks',
                                                            ),
                                                    ),
                                                'view_position' => 1200,
                                                'privilege_data' => NULL,
                                            ),
                                        2 =>
                                            array (
                                                'name' => 'Workflows',
                                                'description' => 'Workflows',
                                                'label' => 'workflows',
                                                'actions' =>
                                                    array (
                                                        'create' =>
                                                            array (
                                                                'label' => 'Manage',
                                                                'tooltip' => '',
                                                                'description' => 'Approval matrix for payouts, payout links and invoices',
                                                            ),
                                                    ),
                                                'view_position' => 1300,
                                                'privilege_data' => NULL,
                                            ),
                                        3 =>
                                            array (
                                                'name' => 'Accounting Integration',
                                                'description' => 'Manage integration with your accounting tool',
                                                'label' => 'accountingIntegrations',
                                                'actions' =>
                                                    array (
                                                        'create' =>
                                                            array (
                                                                'label' => 'Create, Sync, Delete',
                                                                'tooltip' => '',
                                                                'description' => 'Create, sync and delete the accounting integration',
                                                            ),
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => '',
                                                                'description' => 'View and join waitlist for accounting integration',
                                                            ),
                                                    ),
                                                'view_position' => 1400,
                                                'privilege_data' => NULL,
                                            ),
                                        4 =>
                                            array (
                                                'name' => 'Billing',
                                                'description' => 'Billing plan and monthly invoices for fees and taxes charged by RazorpayX',
                                                'label' => 'billing',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'label' => 'View',
                                                                'tooltip' => '',
                                                                'description' => 'View and download plan, charges on transactions',
                                                            ),
                                                    ),
                                                'view_position' => 1500,
                                                'privilege_data' => NULL,
                                            ),
                                    ),
                            ),
                        6 =>
                            array (
                                'name' => 'Account Setting',
                                'description' => 'A/c setting test description',
                                'label' => 'account_setting',
                                'parent_id' => NULL,
                                'actions' =>
                                    array (
                                    ),
                                'view_position' => 10000,
                                'privilege_data' =>
                                    array (
                                        0 =>
                                            array (
                                                'name' => 'Tax Setting',
                                                'description' => 'Tax setting test description',
                                                'label' => 'tax_setting',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'tooltip' => 'Tooltip',
                                                                'description' => 'Access policy test description',
                                                            ),
                                                        'create' =>
                                                            array (
                                                                'tooltip' => 'Tooltip',
                                                                'description' => 'Access policy test description',
                                                            ),
                                                    ),
                                                'view_position' => 10000,
                                                'tool_tip' => 'PRIVILEGE 2',
                                                'privilege_data' => NULL,
                                            ),
                                        1 =>
                                            array (
                                                'name' => 'Business Setting',
                                                'description' => 'Business setting test description',
                                                'label' => 'business_setting',
                                                'actions' =>
                                                    array (
                                                        'view' =>
                                                            array (
                                                                'tooltip' => 'Tooltip',
                                                                'description' => 'Access policy test description',
                                                            ),
                                                        'create' =>
                                                            array (
                                                                'tooltip' => 'Tooltip',
                                                                'description' => 'Access policy test description',
                                                            ),
                                                    ),
                                                'view_position' => 10000,
                                                'tool_tip' => 'PRIVILEGE 3',
                                                'privilege_data' => NULL,
                                            ),
                                    ),
                            ),
                    ),
            )
        ],
    ],
];
