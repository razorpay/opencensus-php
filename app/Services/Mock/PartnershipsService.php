<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response;
use \RZP\Services\Partnerships\PartnershipsService as BasePartnerships;

class PartnershipsService extends BasePartnerships
{
    public function createAuditLog($parameters, $mode)
    {
        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode([
            "audit_log" => [
                "id" => "KO2HxIUhTkQmAC",
                "entity_id" => "12345678901278",
                "entity_type" => "partner_config",
                "actor_id" => "12345678901234",
                "actor_email" => "abc@razorpay.com",
                "entity_data" => [
                    "amount" => 5000,
                    "pricing_id" => [
                        "price_name" => "test3"
                    ]
                ],
                "modified_at" => "1654865178"
            ]
        ]);
        return $resp;
    }

    public function listAuditLogByEntityIds($parameters)
    {
        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode([
            "audit_log" => [
                [
                    "id" => "KO2HxIUhTkQmAC",
                    "entity_id" => "12345678901278",
                    "entity_type" => "partner_config",
                    "actor_id" => "12345678901234",
                    "actor_email" => "abc@razorpay.com",
                    "entity_data" => [
                        "amount" => 5000,
                        "pricing_id" => [
                            "price_name" => "test3"
                        ]
                    ],
                    "modified_at" => "1654865178"
                ],
                [
                    "id" => "KMtbd981KKh3XB",
                    "entity_id" => "12345678901278",
                    "entity_type" => "partner_config",
                    "actor_id" => "12345678901234",
                    "actor_email" => "abc@razorpay.com",
                    "entity_data" => [
                        "amount" => 2000,
                        "pricing_id" => [
                            "price_name" => "test2"
                        ]
                    ],
                    "modified_at" => "1654865165"
                ]
            ]
        ]);
        return $resp;
    }

    public function listAuditLogByEntityId($parameters)
    {
        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode([
            "audit_log" => [
                "id" => "KO2HxIUhTkQmAC",
                "entity_id" => "12345678901278",
                "entity_type" => "partner_config",
                "actor_id" => "12345678901234",
                "actor_email" => "abc@razorpay.com",
                "entity_data" => [
                    "amount" => 5000,
                    "pricing_id" => [
                        "price_name" => "test3"
                    ]
                ],
                "modified_at" => "1654865178"
            ]
        ]);
        return $resp;
    }
}
