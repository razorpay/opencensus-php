<?php

namespace RZP\Services\Mock;

use WpOrg\Requests\Response;
use RZP\Models\Merchant\Constants;
use RZP\Services\LOSService as BaseLOSService;

class LOSService extends BaseLOSService
{
    const PRODUCT_LIST = [
        [
            "id"          => "EoGaaFNzL1EWBg",
            "name"        => "LOAN",
            "description" => "Loaan ley lo",
            "attributes"  => [
                "limits"                  => [
                    [
                        "limit_type" => "credit_limit",
                        "upper"      => 100000,
                        "lower"      => 1000
                    ]
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
        [
            "id"          => "EqKB9PdCTKCFXo",
            "name"        => "LOC",
            "description" => "Loc ley lo",
            "attributes"  => [
                "limits"                  => [
                    [
                        "limit_type" => "credit_limit",
                        "upper"      => 100000,
                        "lower"      => 1000
                    ]
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
        [
            "id"          => "EzKCyq0So3rVWU",
            "name"        => "CARDS",
            "description" => "Credit CARD",
            "attributes"  => [
                "limits"                  => [
                    [
                        "limit_type" => "credit_limit",
                        "upper"      => 100000,
                        "lower"      => 1000
                    ]
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
        [
            "id"          => "GUFPH0c7xFrkB5",
            "name"        => "Loan",
            "description" => "Loc ley lo",
            "attributes"  => [
                "limits"                  => [
                    [
                        "limit_type" => "credit_limit",
                        "upper"      => 100000,
                        "lower"      => 1000
                    ]
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
        [
            "id"          => "HkZwK1B5L02mD9",
            "name"        => "MARKETPLACE_ES",
            "description" => "Lending for Marketplace sellers",
            "attributes"  => [
                "limits"                  => [
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
        [
            "id"          => "JsP6pHbeMKn10D",
            "name"        => "LOC_CLI",
            "description" => "Line of Credit CLI",
            "attributes"  => [
                "limits"                  => [
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
        [
            "id"          => "JsP6pHbeMKn10E",
            "name"        => "LOC_EMI",
            "description" => "Line of Credit EMI",
            "attributes"  => [
                "limits"                  => [
                    [
                        "limit_type" => "credit_limit",
                        "upper"      => 100000,
                        "lower"      => 1000
                    ]
                ],
                "credit_offer_attributes" => [
                ]
            ]
        ],
    ];

    public function sendRequest(
        string $url,
        array  $body = [],
        array  $headers = [],
        array  $options = []): mixed
    {
        $response = null;

        if ($url === Constants::CREATE_CAPITAL_APPLICATION_LOS_URL)
        {
            $response = ["success" => "ok"];
        }

        if ($url === Constants::GET_PRODUCTS_LOS_URL)
        {
            $response = [
                "products" => self::PRODUCT_LIST,
            ];
        }

        if ($url === Constants::GET_CAPITAL_APPLICATIONS_BULK_URL)
        {
            $response = [
                "response" => []
            ];

            if (in_array('10000000000009', $body['merchant_ids']) === true)
            {
                $response["response"]['10000000000009']['partner_applications'] = [
                    [
                        "id"                           => "L6P5IPYtcjt7jp",
                        "stage"                        => "Bureau Submission",
                        "state"                        => "STATE_CREATED",
                        "business_name"                => "Nice Technologies",
                        "account_name"                 => "Nice Technologies",
                        "contact_mobile"               => "+918877665",
                        "email"                        => "nice.new.tech+17@gmail.com",
                        "annual_turnover_min"          => "100000",
                        "annual_turnover_max"          => "2000000",
                        "company_address_line_1"       => "Dimholt Industries Pvt. Ltd, BH11",
                        "company_address_line_2"       => "Bada Mandir, MIDC Phase 5",
                        "company_address_city"         => "Balapur",
                        "company_address_state"        => "MH",
                        "company_address_line_country" => "India",
                        "company_address_pincode"      => "442004",
                        "business_type"                => "PROPRIETORSHIP",
                        "business_vintage"             => "UNKNOWN",
                        "gstin"                        => "37ABCBS1234N1Z1",
                        "promoter_pan"                 => "ABCPS1234N",
                        "created_at"                   => "2023-01-20T10:46:39Z",
                        "updated_at"                   => "2023-01-20T15:14:01Z"
                    ]
                ];
            }

            if (in_array('10000000000010', $body['merchant_ids']) === true)
            {
                $response["response"]['10000000000010']['partner_applications'] = [
                    [
                        "id"                           => "L6PKlqh6cgCI5W",
                        "stage"                        => "Bureau Submission",
                        "state"                        => "STATE_CREATED",
                        "business_name"                => "Nice Technologies",
                        "account_name"                 => "Nice Technologies",
                        "contact_mobile"               => "+918877665",
                        "email"                        => "nice.new.tech+18@gmail.com",
                        "annual_turnover_min"          => "100000",
                        "annual_turnover_max"          => "2000000",
                        "company_address_line_1"       => "Dimholt Industries Pvt. Ltd, BH11",
                        "company_address_line_2"       => "Bada Mandir, MIDC Phase 5",
                        "company_address_city"         => "Balapur",
                        "company_address_state"        => "MH",
                        "company_address_line_country" => "India",
                        "company_address_pincode"      => "442004",
                        "business_type"                => "PROPRIETORSHIP",
                        "business_vintage"             => "UNKNOWN",
                        "gstin"                        => "37ABCBS1234N1Z1",
                        "promoter_pan"                 => "ABCPS1234N",
                        "created_at"                   => "2023-01-20T11:01:18Z",
                        "updated_at"                   => "2023-01-20T15:14:02Z"
                    ]
                ];
            }
        }

        $resp              = new Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode($response);

        return $resp;
    }
}
