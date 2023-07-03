<?php

use RZP\Models\VirtualAccount;

return [
    env('APP_V2_ID_VENDOR_PAYMENTS')            => [
        "name"        => "vendor_payments",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_VENDOR_PAYMENTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_VENDOR_PAYMENTS'),
                "mode"     => "live",
                "roles"    => [
                    "app.vendor_payments"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_VENDOR_PAYMENTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_VENDOR_PAYMENTS'),
                "mode"     => "test",
                "roles"    => [
                    "app.vendor_payments"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_DASHBOARD')                  => [
        "name"        => "dashboard",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_DASHBOARD'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_DASHBOARD'),
                "mode"     => "live",
                "roles"    => [
                    "app.dashboard"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_DASHBOARD'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_DASHBOARD'),
                "mode"     => "test",
                "roles"    => [
                    "app.dashboard"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MERCHANT_DASHBOARD')         => [
        "name"        => "merchant_dashboard",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MERCHANT_DASHBOARD'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MERCHANT_DASHBOARD'),
                "mode"     => "live",
                "roles"    => [
                    "app.merchant_dashboard"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MERCHANT_DASHBOARD'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MERCHANT_DASHBOARD'),
                "mode"     => "test",
                "roles"    => [
                    "app.merchant_dashboard"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_ADMIN_DASHBOARD')            => [
        "name"        => "admin_dashboard",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_ADMIN_DASHBOARD'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_ADMIN_DASHBOARD'),
                "mode"     => "live",
                "roles"    => [
                    "app.admin_dashboard"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_ADMIN_DASHBOARD'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_ADMIN_DASHBOARD'),
                "mode"     => "test",
                "roles"    => [
                    "app.admin_dashboard"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_FRONTEND_GRAPHQL')           => [
        "name"        => "frontend_graphql",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_FRONTEND_GRAPHQL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_FRONTEND_GRAPHQL'),
                "mode"     => "live",
                "roles"    => [
                    "app.frontend_graphql"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_FRONTEND_GRAPHQL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_FRONTEND_GRAPHQL'),
                "mode"     => "test",
                "roles"    => [
                    "app.frontend_graphql"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SALESFORCE')                 => [
        "name"        => "salesforce",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SALESFORCE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SALESFORCE'),
                "mode"     => "live",
                "roles"    => [
                    "app.salesforce"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SALESFORCE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SALESFORCE'),
                "mode"     => "test",
                "roles"    => [
                    "app.salesforce"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MOCK_GATEWAYS')              => [
        "name"        => "mock_gateways",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MOCK_GATEWAYS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MOCK_GATEWAYS'),
                "mode"     => "live",
                "roles"    => [
                    "app.mock_gateways"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MOCK_GATEWAYS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MOCK_GATEWAYS'),
                "mode"     => "test",
                "roles"    => [
                    "app.mock_gateways"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_DASHBOARD_GUEST')            => [
        "name"        => "dashboard_guest",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_DASHBOARD_GUEST'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_DASHBOARD_GUEST'),
                "mode"     => "live",
                "roles"    => [
                    "app.dashboard_guest"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_DASHBOARD_GUEST'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_DASHBOARD_GUEST'),
                "mode"     => "test",
                "roles"    => [
                    "app.dashboard_guest"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_DASHBOARD_INTERNAL')         => [
        "name"        => "dashboard_internal",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_DASHBOARD_INTERNAL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_DASHBOARD_INTERNAL'),
                "mode"     => "live",
                "roles"    => [
                    "app.dashboard_internal"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_DASHBOARD_INTERNAL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_DASHBOARD_INTERNAL'),
                "mode"     => "test",
                "roles"    => [
                    "app.dashboard_internal"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PAYOUT_LINKS')               => [
        "name"        => "payout_links",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PAYOUT_LINKS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PAYOUT_LINKS'),
                "mode"     => "live",
                "roles"    => [
                    "app.payout_links"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PAYOUT_LINKS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PAYOUT_LINKS'),
                "mode"     => "test",
                "roles"    => [
                    "app.payout_links"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_EXPRESS')                    => [
        "name"        => "express",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_EXPRESS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_EXPRESS'),
                "mode"     => "live",
                "roles"    => [
                    "app.express"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_EXPRESS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_EXPRESS'),
                "mode"     => "test",
                "roles"    => [
                    "app.express"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CRON')                       => [
        "name"        => "cron",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CRON'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CRON'),
                "mode"     => "live",
                "roles"    => [
                    "app.cron"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CRON'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CRON'),
                "mode"     => "test",
                "roles"    => [
                    "app.cron"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SUBSCRIPTIONS')              => [
        "name"        => "subscriptions",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SUBSCRIPTIONS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SUBSCRIPTIONS'),
                "mode"     => "live",
                "roles"    => [
                    "app.subscriptions"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SUBSCRIPTIONS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SUBSCRIPTIONS'),
                "mode"     => "test",
                "roles"    => [
                    "app.subscriptions"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PAYMENT_LINKS')              => [
        "name"        => "payment_links",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PAYMENT_LINKS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PAYMENT_LINKS'),
                "mode"     => "live",
                "roles"    => [
                    "app.payment_links"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PAYMENT_LINKS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PAYMENT_LINKS'),
                "mode"     => "test",
                "roles"    => [
                    "app.payment_links"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MANDATE_HQ')                 => [
        "name"        => "mandate_hq",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MANDATE_HQ'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MANDATE_HQ'),
                "mode"     => "live",
                "roles"    => [
                    "app.mandate_hq"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MANDATE_HQ'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MANDATE_HQ'),
                "mode"     => "test",
                "roles"    => [
                    "app.mandate_hq"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_KOTAK')                      => [
        "name"        =>  VirtualAccount\Provider::KOTAK,
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_KOTAK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_KOTAK'),
                "mode"     => "live",
                "roles"    => [
                    "app.kotak"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_KOTAK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_KOTAK'),
                "mode"     => "test",
                "roles"    => [
                    "app.kotak"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_YESBANK')                    => [
        "name"        =>  VirtualAccount\Provider::YESBANK,
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_YESBANK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_YESBANK'),
                "mode"     => "live",
                "roles"    => [
                    "app.yesbank"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_YESBANK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_YESBANK'),
                "mode"     => "test",
                "roles"    => [
                    "app.yesbank"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_ICICI')                      => [
        "name"        =>  VirtualAccount\Provider::ICICI,
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_ICICI'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_ICICI'),
                "mode"     => "live",
                "roles"    => [
                    "app.icici"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_ICICI'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_ICICI'),
                "mode"     => "test",
                "roles"    => [
                    "app.icici"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_HDFC_ECMS')                  => [
        "name"        =>  VirtualAccount\Provider::HDFC_ECMS,
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_HDFC_ECMS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_HDFC_ECMS'),
                "mode"     => "live",
                "roles"    => [
                    "app.hdfc_ecms"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_HDFC_ECMS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_HDFC_ECMS'),
                "mode"     => "test",
                "roles"    => [
                    "app.hdfc_ecms"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_RBL')                        => [
        "name"        =>  VirtualAccount\Provider::RBL,
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_RBL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_RBL'),
                "mode"     => "live",
                "roles"    => [
                    "app.rbl"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_RBL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_RBL'),
                "mode"     => "test",
                "roles"    => [
                    "app.rbl"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_ECOM')                       => [
        "name"        => "ecom",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_ECOM'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_ECOM'),
                "mode"     => "live",
                "roles"    => [
                    "app.ecom"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_ECOM'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_ECOM'),
                "mode"     => "test",
                "roles"    => [
                    "app.ecom"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_LOS')                        => [
        "name"        => "los",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_LOS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_LOS'),
                "mode"     => "live",
                "roles"    => [
                    "app.los"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_LOS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_LOS'),
                "mode"     => "test",
                "roles"    => [
                    "app.los"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CAPITAL_CARDS_M2P')          => [
        "name"        => "capital_cards_m2p",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CAPITAL_CARDS_M2P'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CAPITAL_CARDS_M2P'),
                "mode"     => "live",
                "roles"    => [
                    "app.capital_cards_m2p"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CAPITAL_CARDS_M2P'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CAPITAL_CARDS_M2P'),
                "mode"     => "test",
                "roles"    => [
                    "app.capital_cards_m2p"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CAPITAL_CARDS_CLIENT')       => [
        "name"        => "capital_cards_client",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CAPITAL_CARDS_CLIENT'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CAPITAL_CARDS_CLIENT'),
                "mode"     => "live",
                "roles"    => [
                    "app.capital_cards_client"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CAPITAL_CARDS_CLIENT'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CAPITAL_CARDS_CLIENT'),
                "mode"     => "test",
                "roles"    => [
                    "app.capital_cards_client"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CAPITAL_SCORECARD_CLIENT')       => [
        "name"        => "capital_scorecard_client",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CAPITAL_SCORECARD_CLIENT'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CAPITAL_SCORECARD_CLIENT'),
                "mode"     => "live",
                "roles"    => [
                    "app.capital_scorecard"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CAPITAL_SCORECARD_CLIENT'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CAPITAL_SCORECARD_CLIENT'),
                "mode"     => "test",
                "roles"    => [
                    "app.capital_scorecard"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CAPITAL_COLLECTIONS_CLIENT') => [
        "name"        => "capital_collections_client",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CAPITAL_COLLECTIONS_CLIENT'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CAPITAL_COLLECTIONS_CLIENT'),
                "mode"     => "live",
                "roles"    => [
                    "app.capital_collections_client"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CAPITAL_COLLECTIONS_CLIENT'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CAPITAL_COLLECTIONS_CLIENT'),
                "mode"     => "test",
                "roles"    => [
                    "app.capital_collections_client"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_LOC')                        => [
        "name"        => "loc",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_LOC'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_LOC'),
                "mode"     => "live",
                "roles"    => [
                    "app.loc"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_LOC'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_LOC'),
                "mode"     => "test",
                "roles"    => [
                    "app.loc"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_LEEGALITY')                  => [
        "name"        => "leegality",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_LEEGALITY'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_LEEGALITY'),
                "mode"     => "live",
                "roles"    => [
                    "app.leegality"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_LEEGALITY'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_LEEGALITY'),
                "mode"     => "test",
                "roles"    => [
                    "app.leegality"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MAILGUN')                    => [
        "name"        => "mailgun",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MAILGUN'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MAILGUN'),
                "mode"     => "live",
                "roles"    => [
                    "app.mailgun"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MAILGUN'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MAILGUN'),
                "mode"     => "test",
                "roles"    => [
                    "app.mailgun"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_RAVEN')                      => [
        "name"        => "raven",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_RAVEN'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_RAVEN'),
                "mode"     => "live",
                "roles"    => [
                    "app.raven"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_RAVEN'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_RAVEN'),
                "mode"     => "test",
                "roles"    => [
                    "app.raven"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SCROOGE')                    => [
        "name"        => "scrooge",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SCROOGE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SCROOGE'),
                "mode"     => "live",
                "roles"    => [
                    "app.scrooge"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SCROOGE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SCROOGE'),
                "mode"     => "test",
                "roles"    => [
                    "app.scrooge"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_THIRDWATCH_REPORTS')         => [
        "name"        => "thirdwatch_reports",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_THIRDWATCH_REPORTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_THIRDWATCH_REPORTS'),
                "mode"     => "live",
                "roles"    => [
                    "app.thirdwatch_reports"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_THIRDWATCH_REPORTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_THIRDWATCH_REPORTS'),
                "mode"     => "test",
                "roles"    => [
                    "app.thirdwatch_reports"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_THIRDWATCH')                 => [
        "name"        => "thirdwatch",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_THIRDWATCH'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_THIRDWATCH'),
                "mode"     => "live",
                "roles"    => [
                    "app.thirdwatch"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_THIRDWATCH'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_THIRDWATCH'),
                "mode"     => "test",
                "roles"    => [
                    "app.thirdwatch"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_XPAYROLL')                   => [
        "name"        => "xpayroll",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_XPAYROLL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_XPAYROLL'),
                "mode"     => "live",
                "roles"    => [
                    "app.xpayroll"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_XPAYROLL'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_XPAYROLL'),
                "mode"     => "test",
                "roles"    => [
                    "app.xpayroll"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_RAZORFLOW')                  => [
        "name"        => "razorflow",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_RAZORFLOW'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_RAZORFLOW'),
                "mode"     => "live",
                "roles"    => [
                    "app.razorflow"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_RAZORFLOW'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_RAZORFLOW'),
                "mode"     => "test",
                "roles"    => [
                    "app.razorflow"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_HOSTED')                     => [
        "name"        => "hosted",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_HOSTED'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_HOSTED'),
                "mode"     => "live",
                "roles"    => [
                    "app.hosted"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_HOSTED'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_HOSTED'),
                "mode"     => "test",
                "roles"    => [
                    "app.hosted"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_H2H')                        => [
        "name"        => "h2h",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_H2H'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_H2H'),
                "mode"     => "live",
                "roles"    => [
                    "app.h2h"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_H2H'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_H2H'),
                "mode"     => "test",
                "roles"    => [
                    "app.h2h"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MERCHANTS_RISK')             => [
        "name"        => "merchants-risk",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MERCHANTS_RISK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MERCHANTS_RISK'),
                "mode"     => "live",
                "roles"    => [
                    "app.merchants-risk"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MERCHANTS_RISK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MERCHANTS_RISK'),
                "mode"     => "test",
                "roles"    => [
                    "app.merchants-risk"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_AUTH_SERVICE')               => [
        "name"        => "auth_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_AUTH_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_AUTH_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.auth_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_AUTH_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_AUTH_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.auth_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_REPORTING')                  => [
        "name"        => "reporting",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_REPORTING'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_REPORTING'),
                "mode"     => "live",
                "roles"    => [
                    "app.reporting"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_REPORTING'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_REPORTING'),
                "mode"     => "test",
                "roles"    => [
                    "app.reporting"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_VAJRA')                      => [
        "name"        => "vajra",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_VAJRA'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_VAJRA'),
                "mode"     => "live",
                "roles"    => [
                    "app.vajra"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_VAJRA'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_VAJRA'),
                "mode"     => "test",
                "roles"    => [
                    "app.vajra"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_FTS')                        => [
        "name"        => "fts",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_FTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_FTS'),
                "mode"     => "live",
                "roles"    => [
                    "app.fts"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_FTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_FTS'),
                "mode"     => "test",
                "roles"    => [
                    "app.fts"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_REMINDERS')                  => [
        "name"        => "reminders",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_REMINDERS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_REMINDERS'),
                "mode"     => "live",
                "roles"    => [
                    "app.reminders"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_REMINDERS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_REMINDERS'),
                "mode"     => "test",
                "roles"    => [
                    "app.reminders"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_BATCH')                      => [
        "name"        => "batch",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_BATCH'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_BATCH'),
                "mode"     => "live",
                "roles"    => [
                    "app.batch"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_BATCH'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_BATCH'),
                "mode"     => "test",
                "roles"    => [
                    "app.batch"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_STORK')                      => [
        "name"        => "stork",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_STORK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_STORK'),
                "mode"     => "live",
                "roles"    => [
                    "app.stork"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_STORK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_STORK'),
                "mode"     => "test",
                "roles"    => [
                    "app.stork"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MTU_LAMBDA')                 => [
        "name"        => "mtu_lambda",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MTU_LAMBDA'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MTU_LAMBDA'),
                "mode"     => "live",
                "roles"    => [
                    "app.mtu_lambda"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MTU_LAMBDA'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MTU_LAMBDA'),
                "mode"     => "test",
                "roles"    => [
                    "app.mtu_lambda"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_AUTOMATION')                 => [
        "name"        => "automation",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_AUTOMATION'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_AUTOMATION'),
                "mode"     => "live",
                "roles"    => [
                    "app.automation"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_AUTOMATION'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_AUTOMATION'),
                "mode"     => "test",
                "roles"    => [
                    "app.automation"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PERFIOS')                    => [
        "name"        => "perfios",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PERFIOS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PERFIOS'),
                "mode"     => "live",
                "roles"    => [
                    "app.perfios"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PERFIOS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PERFIOS'),
                "mode"     => "test",
                "roles"    => [
                    "app.perfios"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SETTLEMENTS_SERVICE')        => [
        "name"        => "settlements_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SETTLEMENTS_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SETTLEMENTS_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.settlements_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SETTLEMENTS_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SETTLEMENTS_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.settlements_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_TERMINALS_SERVICE')          => [
        "name"        => "terminals_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_TERMINALS_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_TERMINALS_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.terminals_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_TERMINALS_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_TERMINALS_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.terminals_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SPINNAKER')                  => [
        "name"        => "spinnaker",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SPINNAKER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SPINNAKER'),
                "mode"     => "live",
                "roles"    => [
                    "app.spinnaker"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SPINNAKER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SPINNAKER'),
                "mode"     => "test",
                "roles"    => [
                    "app.spinnaker"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MOZART')                     => [
        "name"        => "mozart",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MOZART'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MOZART'),
                "mode"     => "live",
                "roles"    => [
                    "app.mozart"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MOZART'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MOZART'),
                "mode"     => "test",
                "roles"    => [
                    "app.mozart"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CARD_PAYMENT_SERVICE')       => [
        "name"        => "card_payment_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CARD_PAYMENT_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CARD_PAYMENT_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.card_payment_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CARD_PAYMENT_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CARD_PAYMENT_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.card_payment_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_WORKFLOWS')                  => [
        "name"        => "workflows",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_WORKFLOWS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_WORKFLOWS'),
                "mode"     => "live",
                "roles"    => [
                    "app.workflows"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_WORKFLOWS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_WORKFLOWS'),
                "mode"     => "test",
                "roles"    => [
                    "app.workflows"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PG_ROUTER')                  => [
        "name"        => "pg_router",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PG_ROUTER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PG_ROUTER'),
                "mode"     => "live",
                "roles"    => [
                    "app.pg_router"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PG_ROUTER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PG_ROUTER'),
                "mode"     => "test",
                "roles"    => [
                    "app.pg_router"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_FRESHDESK_WEBHOOK')          => [
        "name"        => "freshdesk_webhook",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_FRESHDESK_WEBHOOK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_FRESHDESK_WEBHOOK'),
                "mode"     => "live",
                "roles"    => [
                    "app.freshdesk_webhook"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_FRESHDESK_WEBHOOK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_FRESHDESK_WEBHOOK'),
                "mode"     => "test",
                "roles"    => [
                    "app.freshdesk_webhook"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PGOS')          => [
        "name"        => "pgos",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PGOS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PGOS'),
                "mode"     => "live",
                "roles"    => [
                    "app.pgos"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PGOS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PGOS'),
                "mode"     => "test",
                "roles"    => [
                    "app.pgos"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_DISPUTES')       => [
        "name"        => "disputes",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_DISPUTES'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_DISPUTES'),
                "mode"     => "live",
                "roles"    => [
                    "app.disputes"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_DISPUTES'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_DISPUTES'),
                "mode"     => "test",
                "roles"    => [
                    "app.disputes"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_FRIEND_BUY_WEBHOOK')          => [
        "name"        => "friend_buy_webhook",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_FRIEND_BUY_WEBHOOK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_FRIEND_BUY_WEBHOOK'),
                "mode"     => "live",
                "roles"    => [
                    "app.friend_buy_webhook"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_FRIEND_BUY_WEBHOOK'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_FRIEND_BUY_WEBHOOK'),
                "mode"     => "test",
                "roles"    => [
                    "app.friend_buy_webhook"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_YELLOWMESSENGER')            => [
        "name"        => "yellowmessenger",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_YELLOWMESSENGER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_YELLOWMESSENGER'),
                "mode"     => "live",
                "roles"    => [
                    "app.yellowmessenger"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_YELLOWMESSENGER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_YELLOWMESSENGER'),
                "mode"     => "test",
                "roles"    => [
                    "app.yellowmessenger"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SMART_ROUTING')              => [
        "name"        => "smart_routing",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SMART_ROUTING'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SMART_ROUTING'),
                "mode"     => "live",
                "roles"    => [
                    "app.smart_routing"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SMART_ROUTING'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SMART_ROUTING'),
                "mode"     => "test",
                "roles"    => [
                    "app.smart_routing"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_GUPSHUP')                    => [
        "name"        => "gupshup",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_GUPSHUP'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_GUPSHUP'),
                "mode"     => "live",
                "roles"    => [
                    "app.gupshup"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_GUPSHUP'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_GUPSHUP'),
                "mode"     => "test",
                "roles"    => [
                    "app.gupshup"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_BVS')                        => [
        "name"        => "bvs",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_BVS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_BVS'),
                "mode"     => "live",
                "roles"    => [
                    "app.bvs"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_BVS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_BVS'),
                "mode"     => "test",
                "roles"    => [
                    "app.bvs"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_DOWNTIME_SERVICE')           => [
        "name"        => "downtime_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_DOWNTIME_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_DOWNTIME_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.downtime_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_DOWNTIME_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_DOWNTIME_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.downtime_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_SMS_SYNC')                   => [
        "name"        => "sms_sync",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_SMS_SYNC'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_SMS_SYNC'),
                "mode"     => "live",
                "roles"    => [
                    "app.sms_sync"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_SMS_SYNC'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_SMS_SYNC'),
                "mode"     => "test",
                "roles"    => [
                    "app.sms_sync"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MERCHANT_RISK_ALERTS')       => [
        "name"        => "merchant_risk_alerts",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MERCHANT_RISK_ALERTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MERCHANT_RISK_ALERTS'),
                "mode"     => "live",
                "roles"    => [
                    "app.merchant_risk_alerts"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MERCHANT_RISK_ALERTS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MERCHANT_RISK_ALERTS'),
                "mode"     => "test",
                "roles"    => [
                    "app.merchant_risk_alerts"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_CARE')                       => [
        "name"        => "care",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_CARE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CARE'),
                "mode"     => "live",
                "roles"    => [
                    "app.care"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_CARE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_CARE'),
                "mode"     => "test",
                "roles"    => [
                    "app.care"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_BANKING_ACCOUNT_SERVICE')    => [
        "name"        => "banking_account_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_BANKING_ACCOUNT_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_BANKING_ACCOUNT_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.banking_account_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_BANKING_ACCOUNT_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_BANKING_ACCOUNT_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.banking_account_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_MYOPERATOR')                 => [
        "name"        => "myoperator",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_MYOPERATOR'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_MYOPERATOR'),
                "mode"     => "live",
                "roles"    => [
                    "app.myoperator"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_MYOPERATOR'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_MYOPERATOR'),
                "mode"     => "test",
                "roles"    => [
                    "app.myoperator"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PAYOUTS_SERVICE')            => [
        "name"        => "payouts_service",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PAYOUTS_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PAYOUTS_SERVICE'),
                "mode"     => "live",
                "roles"    => [
                    "app.payouts_service"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PAYOUTS_SERVICE'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PAYOUTS_SERVICE'),
                "mode"     => "test",
                "roles"    => [
                    "app.payouts_service"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_LEDGER')                     => [
        "name"        => "ledger",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_LEDGER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_LEDGER'),
                "mode"     => "live",
                "roles"    => [
                    "app.ledger"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_LEDGER'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_LEDGER'),
                "mode"     => "test",
                "roles"    => [
                    "app.ledger"
                ]
            ]
        ]
    ],
    env('APP_V2_ID_PARTNERSHIPS')                     => [
        "name"        => "partnerships",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PARTNERSHIPS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PARTNERSHIPS'),
                "mode"     => "live",
                "roles"    => [
                    "app.partnerships"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PARTNERSHIPS'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PARTNERSHIPS'),
                "mode"     => "test",
                "roles"    => [
                    "app.partnerships"
                 ]
             ]
         ]
    ],
    env('APP_V2_ID_PAYMENTS_UPI')               => [
        "name"        => "payments_upi",
        "credentials" => [
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_LIVE_PAYMENTS_UPI'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_LIVE_PAYMENTS_UPI'),
                "mode"     => "live",
                "roles"    => [
                    "app.payments_upi"
                ]
            ],
            [
                "username" => env('APP_V2_CREDENTIAL_USERNAME_TEST_PAYMENTS_UPI'),
                "password" => env('APP_V2_CREDENTIAL_PASSWORD_TEST_PAYMENTS_UPI'),
                "mode"     => "test",
                "roles"    => [
                    "app.payments_upi"
                ]
            ]
        ]
    ]
];
