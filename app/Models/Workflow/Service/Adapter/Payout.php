<?php

namespace RZP\Models\Workflow\Service\Adapter;

use RZP\Http\RequestHeader;
use RZP\Models\Payout\Entity;

class Payout extends Base
{
    const APPROVED              = 'approved';

    const REJECTED              = 'rejected';

    const MERCHANT              = 'merchant';

    const SUCCESS_ERROR_CODES   = [200, 201, 409];

    public function getCallBackDetails(array $entityArr, array $input)
    {
        $headers = [
            RequestHeader::X_Creator_Id => $entityArr['user_id'] ?? null,
        ];

        $workflowCallbackPath                   = '/payouts_internal/pout_' . $entityArr['id'];

        $stateCallBackCreatedPath               = '/wf-service/state/callback';

        $stateCallBackProcessedPath             = '/wf-service/state/%s/callback';

        $payload = [
            'queue_if_low_balance' => $this->shouldQueuePayout($input)
        ];

        $callBackDetails = [
            'state_callbacks' => [
                'created' => [
                    'type'      => 'basic',
                    'method'    => 'post',
                    'service'   => Constants::SERVICE_RX . $this->ba->getMode(),
                    'url_path'  => $stateCallBackCreatedPath,
                    'headers'   => $headers,
                    'payload'   => $payload,
                    'response_handler' => [
                        'type'                           => Constants::SUCCESS_STATUS_CODES,
                        Constants::SUCCESS_STATUS_CODES  => self::SUCCESS_ERROR_CODES
                    ]
                ],
                'processed' => [
                    'type'      => 'basic',
                    'method'    => 'patch',
                    'service'   => Constants::SERVICE_RX . $this->ba->getMode(),
                    'url_path'  => $stateCallBackProcessedPath,
                    'headers'   => $headers,
                    'payload'   => $payload,
                    'response_handler' => [
                        'type'                           => Constants::SUCCESS_STATUS_CODES,
                        Constants::SUCCESS_STATUS_CODES  => self::SUCCESS_ERROR_CODES
                    ]
                ]
            ],
            'workflow_callbacks' => [
                'processed' => [
                    'domain_status' => [
                        'approved'  => [
                            'type'      => 'basic',
                            'method'    => 'post',
                            'service'   => Constants::SERVICE_RX . $this->ba->getMode(),
                            'url_path'  => $workflowCallbackPath.'/approve',
                            'headers'   => $headers,
                            'payload'   => $payload,
                            'response_handler' => [
                                'type'                           => Constants::SUCCESS_STATUS_CODES,
                                Constants::SUCCESS_STATUS_CODES  => self::SUCCESS_ERROR_CODES
                            ]
                        ],
                        'rejected' => [
                            'type'      => 'basic',
                            'method'    => 'post',
                            'service'   => Constants::SERVICE_RX . $this->ba->getMode(),
                            'url_path'  => $workflowCallbackPath.'/reject',
                            'headers'   => $headers,
                            'payload'   => $payload,
                            'response_handler' => [
                                'type'                           => Constants::SUCCESS_STATUS_CODES,
                                Constants::SUCCESS_STATUS_CODES  => self::SUCCESS_ERROR_CODES
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $callBackDetails;
    }

    /**
     * @param array $input
     * @return bool
     */
    private function shouldQueuePayout(array $input)
    {
        if (isset($input['queue_if_low_balance']) === true)
        {
            $queueVal = $input['queue_if_low_balance'];

            return $queueVal == '1' or $queueVal === true;
        }

        // queue by default
        return true;
    }

    public function getDiffForWorkflow(array $entityArr)
    {
        return [
            'old' => [
                'merchant_id'   => null,
                'amount'        => null,
            ],
            'new' => [
                'merchant_id'   => $entityArr[Entity::MERCHANT_ID],
                'amount'        => $entityArr[Entity::AMOUNT],
            ]
        ];
    }
}
