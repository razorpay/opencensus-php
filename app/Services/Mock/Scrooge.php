<?php

namespace RZP\Services\Mock;

use RZP\Services\Scrooge as BaseScrooge;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

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

    public function initiateRefundRecon(array $input, bool $throwExceptionOnFailure = false): array
    {
        $refunds = [];

        foreach ($input['refunds'] as $refund)
        {
            $refunds[] = [
                'arn'               => $refund['arn'],
                'gateway_keys'      => [
                    'arn'               => '12345678910',
                    'recon_batch_id'    => $input['batch_id']
                ],
                'reconciled_at'         => 1549108187,
                'refund_id'             => $refund['refund_id'],
                'status'                => 'processed',
                'gateway_settled_at'    => $refund['gateway_settled_at']
            ];
        }

        $response = [
            'body' => [
                'response' => [
                    'batch_id'                  => $input['batch_id'],
                    'chunk_number'              => $input['chunk_number'],
                    'refunds'                   => $refunds,
                    'should_force_update_arn'   => $input['should_force_update_arn'],
                    'source'                    => 'manual',
                ]
            ]
        ];

        return $response;
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
           "success_count": 1,
           "failure_count": 1,
           "errors": [{
              "refund_id": "abc1234d",
              "code": "INVALID_STATE",
              "description": "State transition invalid"
           }]
        }', true);
    }

    public function reverseFailedRefunds(array $input, bool $throwExceptionOnFailure = false): array
    {
        return json_decode('{
           "success_count": 1,
           "failure_count": 1,
           "errors": [{
              "refund_id": "ab123412341234",
              "code": "INVALID_STATE",
              "description": "State transition invalid"
           }]
        }', true);
    }

    public function bulkUpdateRefundReference1(array $input, bool $throwExceptionOnFailure = false): array
    {
        return json_decode('{
            "api_failed_count": 0,
            "api_failures": [],
            "scrooge_failed_count": 0,
            "scrooge_failures": [],
            "success_count": 1,
            "time_taken": 0.24121499061584473
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

    public function fetchRefundInternal($input)
    {
        return [
            'code'     => 200,
            'body'     => [
                'data' => [
                    [
                    'id'                        =>$input['query']['refunds']['id'] ?? 'abcdeferhthjkt',
                    'payment_id'                =>$input['query']['refunds']['payment_id'] ?? '5UWttxtCjkrldV',
                    'merchant_id'               =>'8byazTDARv4Io0',
                    'amount'                    =>69400,
                    'base_amount'               =>69400,
                    'currency'                  =>'INR',
                    'gateway'                   =>'card_fss',
                    'refund_gateway'            =>'card_fss',
                    'gateway_acquirer'          =>'barb',
                    'status'                    =>'processed',
                    'public_status'             =>'',
                    'method'                    =>'card',
                    'bank'                      =>'',
                    'reconciled_at'             =>'',
                    'attempts'                  =>1,
                    'speed_requested'           =>'normal',
                    'mode_requested'            =>'',
                    'speed'                     =>'normal',
                    'fee'                       =>0,
                    'tax'                       =>0,
                    'processed_source'          =>'GATEWAY_REFUND_UNSUPPORTED_FUNDS_MOVED_TO_X',
                    'next_attempt_at'           =>'',
                    'last_attempted_at'         =>1545478867,
                    'processed_at'              =>1594903122,
                    'payment_amount'            =>2905225,
                    'payment_base_amount'       =>2905225,
                    'payment_gateway_captured'  =>1,
                    'payment_service_route'     =>0,
                    'payment_created_at'        =>1527089260,
                    'payment_authorized_at'     =>1527089297,
                    'on_hold_reason'            =>'',
                    'arn'                       =>'',
                    'created_at'                =>1545473781,
                    'updated_at'                =>1628187697,
                    'gateway_amount'            =>'',
                    'gateway_currency'          =>'',
                    'payment_gateway_amount'    =>'',
                    'batch_id'                  =>'BambQLcZFz2aF0',
                    'notes'                     =>'{"refund_type": "C", "reservation_id": "2000614752", "cancellation_id": "9500238892", "cancellation_date": "20181220"}',
                    'meta'                      =>'',
                    'initiation_type'           =>0,
                    'arn_updated_at'            =>'',
                    'debit_validation_batch_id' =>'',
                    'gateway_refund_file_time'  =>'',
                    'terminal_id'               =>'9xU2t8VzevNSUw',
                    'reference1'                =>'',
                    'settled_by'                =>'Razorpay',
                    'receipt'                   =>'9500238892_2000614752',
                    'transaction_id'            =>'BambpNC2hqpZgX',
                    'speed_decisioned'          =>'normal',
                    'payment_captured_at'       =>1527242197,
                    'deleted_at'                =>'',
                    ],
                ],
            ],
        ];
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

    public function getInstantRefundsMode(string $merchantId, array $params): array
    {
        return json_decode('{
            "mode": "IMPS"
        }', true);
    }

    public function fetchRefundCreateData(array $params): array
    {
        return [
            RefundConstants::MODE => 'IMPS',
            RefundConstants::GATEWAY_REFUND_SUPPORT => true,
            RefundConstants::INSTANT_REFUND_SUPPORT => true,
            RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND => null,
        ];
    }

    public function getFileBasedRefunds(array $input): array
    {
        $scroogeResponse = [
            'code'     => 200,
            'body'     => [
                'data' => [],
            ],
        ];

        return $scroogeResponse;
    }

    public function getPublicRefund(string $id, array $params = []): array
    {
        $scroogeResponse = [
            'code' => 200,
            'body' => [
                'status' => 'pending',
            ]
        ];

        return $scroogeResponse;
    }

    public function getRefundsFromPaymentIdAndGatewayId(array $input)
    {
        $scroogeResponse = [
            'body' => [
                'data'      => [],
                'exception' => [],
            ]
        ];

        return $scroogeResponse;
    }

    public function retryRefundsWithVerify(array $input) : array
    {
        $scroogeResponseBody = [];

        if (isset($input['refund_ids']) === true)
        {
            foreach ($input['refund_ids'] as $refundId)
            {
                $scroogeResponseBody[$refundId] = [
                    'error' => null,
                ];
            }
        }

        return [
            'code' => 200,
            'body' => $scroogeResponseBody
        ];
    }

    public function retryRefundsWithoutVerify(array $input) : array
    {
        $scroogeResponseBody = [];

        if (isset($input['refund_ids']) === true)
        {
            foreach ($input['refund_ids'] as $refundId)
            {
                $scroogeResponseBody[$refundId] = [
                    'error' => null,
                ];
            }
        }

        return [
            'code' => 200,
            'body' => $scroogeResponseBody
        ];
    }

    public function retryRefundsViaSourceFundTransfers(array $input) : array
    {
        $scroogeResponseBody = [];

        if (isset($input['refund_ids']) === true)
        {
            foreach ($input['refund_ids'] as $refundId)
            {
                $scroogeResponseBody[$refundId] = [
                    'error' => null,
                ];
            }
        }

        return [
            'code' => 200,
            'body' => $scroogeResponseBody
        ];
    }

    public function retryRefundsViaCustomFundTransfers(array $input) : array
    {
        $scroogeResponseBody = [];

        if (isset($input['refunds']) === true)
        {
            foreach ($input['refunds'] as $refundId => $retryBody)
            {
                $scroogeResponseBody[$refundId] = [
                    'error' => null,
                ];
            }
        }

        return [
            'code' => 200,
            'body' => $scroogeResponseBody
        ];
    }

    public function createNewRefundV2(array $input) : array
    {
        $paymentId = $input['payment_id'] ?? 'pay_HZCakkQ4T6jV32';
        $amount = $input['amount'] ?? 1000;
        $notes = $input['notes'] ?? [];
        $receipt = $input['receipt'] ?? null;
        $speedRequested = $input['speed'] ?? "optimum";
        $speedProcessed = $speedRequested == "normal" ? "normal" : "instant";

        $scroogeResponseBody = [
            "acquirer_data"   => [],
            "amount"          => $amount,
            "batch_id"        => "",
            "created_at"      => 1626357774,
            "currency"        => "INR",
            "entity"          => "refund",
            "id"              => "rfnd_HZETs6HPiyDr8n",
            "notes"           => $notes,
            "payment_id"      => $paymentId,
            "receipt"         => $receipt,
            "speed_processed" => $speedProcessed,
            "speed_requested" => $speedRequested,
            "status"          => "processed"
        ];

        $scroogeResponse = [
            'code' => 200,
            'body' => $scroogeResponseBody
        ];

        if (in_array($scroogeResponse['code'], [200, 201, "200", "201"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        return $scroogeResponse['body'];
    }

    public function refundsFetchById($id, array $input): array
    {
        $scroogeResponseBody = [
            "acquirer_data"   => [],
            "amount"          => 100,
            "batch_id"        => "",
            "created_at"      => 1626357774,
            "currency"        => "INR",
            "entity"          => "refund",
            "id"              => $id,
            "notes"           => [],
            "payment_id"      => "pay_HZETs6HPiyDr8n",
            "receipt"         => "",
            "speed_processed" => "normal",
            "speed_requested" => "optimum",
            "status"          => "processed"
        ];

        return $scroogeResponseBody;
    }

    public function refundsFetchMultiple(array $input): array
    {
        $scroogeResponseBody = [
            "count" => 2,
            "entity" => "collection",
            "items" => [
                [
                    "acquirer_data"   => [],
                    "amount"          => 100,
                    "batch_id"        => "",
                    "created_at"      => 1626357774,
                    "currency"        => "INR",
                    "entity"          => "refund",
                    "id"              => "rfnd_JGSYige61Z8Qkr",
                    "notes"           => [],
                    "payment_id"      => "pay_HZETs6HPiyDr8n",
                    "receipt"         => "",
                    "speed_processed" => "normal",
                    "speed_requested" => "optimum",
                    "status"          => "processed"
                ],
                [
                    "acquirer_data"   => [],
                    "amount"          => 100,
                    "batch_id"        => "",
                    "created_at"      => 1626357775,
                    "currency"        => "INR",
                    "entity"          => "refund",
                    "id"              => "rfnd_JGSYige61Z8Qkr",
                    "notes"           => [],
                    "payment_id"      => "pay_HZETs6HPiyDr8n",
                    "receipt"         => "",
                    "speed_processed" => "normal",
                    "speed_requested" => "optimum",
                    "status"          => "processed"
                ],
            ]
        ];

        return $scroogeResponseBody;
    }

    public function refundsFetchByPayment($paymentId, array $input): array
    {
        $scroogeResponseBody = [
            "count" => 2,
            "entity" => "collection",
            "items" => [
                [
                    "acquirer_data"   => [],
                    "amount"          => 100,
                    "batch_id"        => "",
                    "created_at"      => 1626357774,
                    "currency"        => "INR",
                    "entity"          => "refund",
                    "id"              => "rfnd_JGSYige61Z8Qkr",
                    "notes"           => [],
                    "payment_id"      => $paymentId,
                    "receipt"         => "",
                    "speed_processed" => "normal",
                    "speed_requested" => "optimum",
                    "status"          => "processed"
                ],
                [
                    "acquirer_data"   => [],
                    "amount"          => 100,
                    "batch_id"        => "",
                    "created_at"      => 1626357775,
                    "currency"        => "INR",
                    "entity"          => "refund",
                    "id"              => "rfnd_JGSYige61Z8Qkr",
                    "notes"           => [],
                    "payment_id"      => $paymentId,
                    "receipt"         => "",
                    "speed_processed" => "normal",
                    "speed_requested" => "optimum",
                    "status"          => "processed"
                ],
            ]
        ];

        return $scroogeResponseBody;
    }

    public function refundsFetchByIdAndPayment($paymentId, $refundId): array
    {
        $scroogeResponseBody = [
            "acquirer_data"   => [],
            "amount"          => 100,
            "batch_id"        => "",
            "created_at"      => 1626357774,
            "currency"        => "INR",
            "entity"          => "refund",
            "id"              => $refundId,
            "notes"           => [],
            "payment_id"      => $paymentId,
            "receipt"         => "",
            "speed_processed" => "normal",
            "speed_requested" => "optimum",
            "status"          => "processed"
        ];

        return $scroogeResponseBody;
    }

    public function updateRefund($refundId, array $input): array
    {
        $scroogeResponseBody = [
            "acquirer_data"   => [],
            "amount"          => 100,
            "batch_id"        => "",
            "created_at"      => 1626357774,
            "currency"        => "INR",
            "entity"          => "refund",
            "id"              => $refundId,
            "notes"           => ["scrooge" => "welcome"],
            "payment_id"      => "pay_HZETs6HPiyDr8n",
            "receipt"         => "",
            "speed_processed" => "normal",
            "speed_requested" => "optimum",
            "status"          => "processed"
        ];

        $scroogeResponse = [
            'code' => 200,
            'body' => $scroogeResponseBody
        ];

        if ($refundId === 'rfnd_UpdateError001')
        {
            $scroogeResponse['code'] = 400;
            $scroogeResponse['body'] = [
                "internal_error" => [
                    "code"=> "BAD_REQUEST_INVALID_ID",
                    "message"=> "the id provided does not exist",
                    "sub_code"=> ""
                ],
                "public_error"=> [
                    "code"=> "BAD_REQUEST_ERROR",
                    "message"=> "The id provided does not exist"
                ]
            ];
        }

        if (in_array($scroogeResponse['code'], [200, "200"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        return $scroogeResponse['body'];
    }

    public function fetchBulkGatewayKeys($input)
    {
        $responseBody = [];

        foreach ($input['refund_ids'] as $refund_id)
        {
            $row = [];

            foreach ($input['gateway_key_names'] as $gateway_key_name)
            {
                $row[$gateway_key_name] = $gateway_key_name.$refund_id;
            }

            $responseBody[$refund_id] = $row;
        }

        return ['data' => $responseBody];
    }
}
