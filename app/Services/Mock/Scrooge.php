<?php

namespace RZP\Services\Mock;

use RZP\Services\Scrooge as BaseScrooge;

class Scrooge extends BaseScrooge
{
    public function initiateRefund(array $input, bool $throwExceptionOnFailure = false): array
    {
        return
            [
                "message" => "Refund process successfully initiated."
            ];
    }

    public function initiateRefundRetry($input, bool $throwExceptionOnFailure = false): array
    {
        return
            [
                "status" => "Processed"
            ];
    }

    public function getReports(array $input): array
    {
        return json_decode('{"data": [
                {
                  "gateway": "axis_migs",
                  "method": "card",
                  "aging": {
                    "today": {
                      "count": 24,
                      "from": "1234567890",
                      "to": "9876543210"
                    },
                    "yesterday": {
                      "count": 14,
                      "from": "1234567890",
                      "to": "9876543210"
                    },
                    "last_7days": {
                      "count": 100,
                      "from": "1234567890",
                      "to": "9876543210"
                    },
                    "current_month": {
                      "count": 130,
                      "from": "1234567890",
                      "to": "9876543210"
                    },
                    "last_month": {
                      "count": 150,
                      "from": "1234567890",
                      "to": "9876543210"
                    },
                    "before_last_month": {
                      "count": 2000,
                      "from": "1234567890",
                      "to": "9876543210"
                    }
                  }
                }
              ]}',true);
    }

    public function bulkUpdateRefundStatus(array $input, bool $throwExceptionOnFailure = false): array
    {
        return json_decode('{
               "errors": [{
                  "refund_id": "abc1234d",
                  "code": "INVALID_STATE",
                  "description": "State transition invalid"
               }]
            }', true);
    }

    public function getRefunds(array $input): array
    {
        return json_decode('{
              "refunds": [{
                "id": "abcd1234",
                "payment_id": "5UWttxtCjkrldV",
                "amount": "100",
                "payment_amount": "200",
                "ARN": "",
                "is_partial": false,
                "state": "INIT",
                "status_code": "",
                "status_message": "",
                "next_retry_at": 1524673700,
                "created_at": 1524673623,
                "updated_at": 1524673623
              }, {
                "id": "abcd1234",
                "payment_id": "5UWttxtCjkrldV",
                "amount": "100",
                "payment_amount": "200",
                "ARN": "",
                "is_partial": false,
                "state": "INIT",
                "status_code": "",
                "status_message": "",
                "next_retry_at": 1524673700,
                "created_at": 1524673623,
                "updated_at": 1524673623
              }]
            }',true);
    }

    public function getRefund(string $id): array
    {
        return json_decode('{
                "amount": 100,
                "arn": "",
                "attempts": 0,
                "bank": "",
                "base_amount": 100,
                "created_at": 1524673623,
                "currency": "INR",
                "gateway": "Sharp",
                "gateway_keys": {
                    "created_at": 0,
                    "id": 0,
                    "merchant_id": "",
                    "name": "",
                    "refund_id": "",
                    "updated_at": 0,
                    "value": ""
                },
                "id": "'.$id.'",
                "is_reconciled": false,
                "last_attempted_at": 0,
                "merchant_id": "3UWtt000Cjkrld",
                "method": "card",
                "on_hold_reason": "",
                "payment_amount": 100,
                "payment_base_amount": 100,
                "payment_created_at": 1524673623,
                "payment_gateway_captured": false,
                "payment_id": "5UWttxtCjkrldV",
                "status": "init",
                "updated_at": 1537266043
            }');
    }
}
