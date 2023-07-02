<?php

namespace RZP\Services\Mock;

use GuzzleHttp\Psr7\Response;

use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Models\Batch\Type;
use RZP\Exception\ServerNotFoundException;
use RZP\Services\BatchMicroService as BaseBatchMicroService;

class BatchMicroService extends BaseBatchMicroService
{
    public function validateFile(array $input, Merchant\Entity $merchant)
    {
        return [
            'created_at'       => 1551782255,
            'updated_at'       => 1551782255,
            'entity_id'        => 'C28Q0mJgoSfWC1',
            'name'             => 0,
            'batch_type_id'    => $input['type'],
            'is_scheduled'     => false,
            'upload_count'     => 0,
            'total_count'      => 1,
            'failure_count'    => 0,
            'success_count'    => 0,
            'amount'           => 0,
            'attempts'         => 0,
            'status'           => 'CREATED',
            'processed_amount' => 0
        ];
    }

    public function forwardToBatchServiceRequest(array $input, Merchant\Entity $merchant, FileStore\Entity $ufhFile = null)
    {
        if (isset($input['type']) and
            ($input['type'] === 'credit'))
        {
            return [
                'id'               => substr($ufhFile->getName(), -14),
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'name'             => 0,
                'batch_type_id'    => 'credit',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 1,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'CREATED',
                'processed_amount' => 0
            ];
        }

        if (isset($input['type']) and
            ($input['type'] === 'payout_approval'))
        {
            return [
                'entity'           => 'batch',
                'type'             => 'payout_approval',
                'status'           => 'created',
                'name'             => 'My Payout Approval',
                'total_count'      => 1
            ];
        }

        if (isset($input['type']) and
            ($input["type"] === Type::MERCHANT_UPLOAD_MIQ))
        {
            return [
                'id'               => substr($ufhFile->getName(), -14),
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'creator_type'     => 'admin',
                'creator_id'       => 'D68mFOmRSoDtok',
                'name'             => null,
                'batch_type_id'    => Type::MERCHANT_UPLOAD_MIQ,
                'type'             => Type::MERCHANT_UPLOAD_MIQ,
                'mode'             => 'test',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 1,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'created',
                'processed_amount' => 0,
                'settings'         => [],
            ];
        }

        if (isset($input['type']) and
            ($input['type'] === 'nach'))
        {
            return [
                'id'               => substr($ufhFile->getName(), -14),
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'name'             => 0,
                'batch_type_id'    => 'nach',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 1,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'CREATED',
                'processed_amount' => 0
            ];
        }

        if (isset($input['type']) and
            ($input['type'] === 'emandate'))
        {
            return [
                'id'               => substr($ufhFile->getName(), -14),
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'name'             => 0,
                'batch_type_id'    => 'emandate',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 1,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'CREATED',
                'processed_amount' => 0
            ];
        }

        if (isset($input['type']) and
            ($input['type'] === 'enach_npci_netbanking'))
        {
            return [
                'id'               => substr($ufhFile->getName(), -14),
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'name'             => 0,
                'batch_type_id'    => 'enach_npci_netbanking',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 1,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'CREATED',
                'processed_amount' => 0
            ];
        }

        if (isset($input['type']) and
            ($input['type'] === 'payout'))
        {
            return [
                'id'               => substr($ufhFile->getName(), -14),
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'name'             => 0,
                'batch_type_id'    => 'payout',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 1,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'CREATED',
                'processed_amount' => 1000
            ];
        }

        return [
            'id'               => 'C3fzDCb4hA4F6b',
            'created_at'       => 1551782255,
            'updated_at'       => 1551782255,
            'entity_id'        => 'C28Q0mJgoSfWC1',
            'name'             => 0,
            'batch_type_id'    => 'payment_link',
            'is_scheduled'     => false,
            'upload_count'     => 0,
            'total_count'      => 3,
            'failure_count'    => 0,
            'success_count'    => 0,
            'amount'           => 0,
            'attempts'         => 0,
            'status'           => 'CREATED',
            'processed_amount' => 0
        ];
    }

    public function formAndGetMultipartPayload(array $input, Merchant\Entity $merchant)
    {

    }

    public function getBatchesFromBatchServiceAndMerge(array $fetchResult, array $input, Merchant\Entity $merchant = null): array
    {
        $result = [
            'id'     => 'batch_00000000000001',
            'type'   => 'payment_link',
            'creator_id' => 'MerchantUser01',
            'status' => 'created',
            'config' => [
                'sms_notify'   => '0',
                'email_notify' => '0',
            ],
        ];

        array_push($fetchResult['items'], $result);

        $fetchResult['count'] = $fetchResult['count'] + 1;

        return $fetchResult;
    }

    public function downloadS3UrlForBatchOrFileStore(string $id, string $batchOrFileStore, string $merchantId = null)
    {
        if ($id === 'batch_C7e2YqUIpZ2KwZ' and
            $merchantId ==='10000000000000')
        {
            return 'www.s3.download.com';
        }

        throw new ServerNotFoundException('Batch Id Not Found', ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND);
    }

    public function getResponseFromBatchService(string $relativeUrl, string $method, array $options, array $input = null): array
    {
        //
        //  Mocking and returning different scenarios/responses
        //

        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            if ($method === Requests::GET)
            {
                $body = $this->getBatchesResponse();
            }
            else
            {
                if ($method === Requests::PATCH)
                {
                    $body = $this->updateSettingsBatchResponse();
                }
            }
        }
        else
        {
            if (strpos($relativeUrl, 'C3fzDCb4hA4F6b') !== false)
            {
                $body = $this->getBatchResponse();
            }
            else
            {
                throw new Exception\ServerNotFoundException(ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND,
                                                            'Service Down Exception');
            }
        }

        $response = new Response(200, ['X-Foo' => 'Bar'], json_encode($body), '1.1');

        return json_decode($response->getBody(), true);
    }

    private function getBatchResponse()
    {
        return [
            'id'               => 'C3fzDCb4hA4F6b',
            'created_at'       => 1551782255,
            'updated_at'       => 1551782255,
            'entity_id'        => '10000000000000',
            'name'             => null,
            'batch_type_id'    => 'payment_link',
            'is_scheduled'     => false,
            'upload_count'     => 0,
            'total_count'      => 4,
            'processed_count'  => 4,
            'success_count'    => 0,
            'failure_count'    => 0,
            'attempts'         => 0,
            'status'           => 'COMPLETED',
            'amount'           => 3799,
            'processed_amount' => 0,
            'creator_id'       => 'MerchantUser01',
            'config'           => [
                'account_number' => '2224440041626905'
            ]
        ];
    }

    private function getBatchesResponse()
    {
        return [
            'count' => 2,
            'data'  => [
                [
                    'created_at'       => 1557254046,
                    'updated_at'       => 1557254086,
                    'id'               => 'CSjjzIz2AGrISq',
                    'entity_id'        => 'BQXxEcUzUAP0Qr',
                    'name'             => 'kbkvk',
                    'batch_type_id'    => 'payment_link',
                    'mode'             => 'test',
                    'is_scheduled'     => false,
                    'upload_count'     => 0,
                    'processed_count'  => 11,
                    'failure_count'    => 0,
                    'total_count'      => 11,
                    'success_count'    => 11,
                    'attempts'         => 0,
                    'status'           => 'COMPLETED',
                    'settings'         => [
                        'draft'        => '0',
                        'sms_notify'   => '1',
                        'email_notify' => '0',
                    ],
                    'amount'           => 1155,
                    'processed_amount' => 1155,
                ],
                [
                    'created_at'       => 1557254046,
                    'updated_at'       => 1557254086,
                    'id'               => 'CSdhEZBIsG02UK',
                    'entity_id'        => 'BQXxEcUzUAP0Qr',
                    'name'             => 'kbkvk',
                    'batch_type_id'    => 'payment_link',
                    'mode'             => 'test',
                    'is_scheduled'     => false,
                    'upload_count'     => 0,
                    'processed_count'  => 11,
                    'failure_count'    => 0,
                    'total_count'      => 11,
                    'success_count'    => 11,
                    'attempts'         => 0,
                    'status'           => 'COMPLETED',
                    'settings'         => [
                        'name'     => 'pankaj',
                        'send_sms' => true
                    ],
                    'amount'           => 1155,
                    'processed_amount' => 1155,
                ],
            ],
        ];
    }

    private function updateSettingsBatchResponse()
    {
        return [
            'created_at'       => 1557219569,
            'updated_at'       => 1557304243,
            'id'               => 'CSZx0EmFsgAh8H',
            'entity_id'        => 'BQXxEcUzUAP0Qr',
            'name'             => 'kbkvk',
            'batch_type_id'    => 'payment_link',
            'mode'             => 'test',
            'is_scheduled'     => false,
            'upload_count'     => 0,
            'processed_count'  => 11,
            'failure_count'    => 0,
            'total_count'      => 11,
            'success_count'    => 11,
            'attempts'         => 0,
            'status'           => 'COMPLETED',
            'settings'         => [
                'draft'        => 1,
                'sms_notify'   => 0,
                'email_notify' => 0
            ],
            'amount'           => 1155,
            'processed_amount' => 1155
        ];
    }

    public function isCompletelyMigratedBatchType(string $type): bool
    {
        switch ($type) {
            case Type::MERCHANT_UPLOAD_MIQ:
                return true;
            default:
                return false;
        }
    }

    public function getBatchEntries(string $batchId, array $input, Merchant\Entity $merchant = null): array
    {
        return [
            'count' => 3,
            'type'  => 'java.util.Collection',
            'data'  => [
                [
                    'created_at'        => 1681121569,
                    'updated_at'        => 1681121569,
                    'id'                => 'Lc3FCR5UkORots',
                    'batch_id'          => 'Lc3D38BgW3c77T',
                    'sequence_number'   => 7,
                    'row_data'          => '{\"column name 1\":\"value 1\",\"column name 2\":\"value 1\"}',
                    'response_data'     => null,
                    'status'            => "CREATED",
                ],
                [
                    'created_at'        => 1681121569,
                    'updated_at'        => 1681121569,
                    'id'                => 'Lc3FCR0jOZyfZm',
                    'batch_id'          => 'Lc3D38BgW3c77T',
                    'sequence_number'   => 6,
                    'row_data'          => '{\"column name 1\":\"value 2\",\"column name 1\":\"value 2\"}',
                    'response_data'     => null,
                    'status'            => "CREATED",
                ],
                [
                    'created_at'        => 1681121569,
                    'updated_at'        => 1681121569,
                    'id'                => 'Lc3FCQuFNq0CR1',
                    'batch_id'          => 'Lc3D38BgW3c77T',
                    'sequence_number'   => 5,
                    'row_data'          => '{\"column name 1\":\"value 3\",\"column name 1\":\"value 3\"}',
                    'response_data'     => null,
                    'status'            => "CREATED",
                ]
            ]
        ];
    }

    public function processBatch(string $previousBatchId, array $input, Merchant\Entity $merchant)
    {
        return [
            'id'               => 'C3fzDCb4hA4F6b',
            'created_at'       => 1551782255,
            'updated_at'       => 1551782255,
            'entity_id'        => 'C28Q0mJgoSfWC1',
            'name'             => 0,
            'batch_type_id'    => 'payout',
            'is_scheduled'     => false,
            'upload_count'     => 0,
            'total_count'      => 3,
            'failure_count'    => 0,
            'success_count'    => 0,
            'amount'           => 0,
            'attempts'         => 0,
            'status'           => 'CREATED',
            'processed_amount' => 0
        ];
    }
}
