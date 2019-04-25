<?php

namespace RZP\Services\Mock;

use GuzzleHttp\Psr7\Response;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Exception\ServerNotFoundException;
use RZP\Services\BatchMicroService as BaseBatchMicroService;

class BatchMicroService extends BaseBatchMicroService
{

    public function forwardToBatchServiceRequest(array $input, Merchant\Entity $merchant, FileStore\Entity $ufhFile = null)
    {
        return [
            'id'            => 'C3fzDCb4hA4F6b',
            'created_at'    => 1551782255,
            'updated_at'    => 1551782255,
            'entity_id'     => 'C28Q0mJgoSfWC1',
            'name'          => 0,
            'batch_type_id' => "payment_link",
            'is_scheduled'  => false,
            'upload_count'  => 0,
            'total_count'   => 3,
            'failure_count' => 0,
            'success_count' => 0,
            'amount'        => 0,
            'attempts'      => 0,
            'status'        => 'CREATED',
            'processed_amount' => 0
        ];
    }

    public function formAndGetMultipartPayload(array $input, Merchant\Entity $merchant)
    {

    }

    public function getBatchesFromBatchServiceAndMerge(array $fetchResult, array $input, Merchant\Entity $merchant = null) : array
    {
        $result = [
            'id'        => 'batch_00000000000001',
            'type'      => 'payment_link',
            'status'    => 'created',
            'config'    => [
                'sms_notify'    => '0',
                'email_notify'  => '0',
            ],
        ];

        array_push($fetchResult['items'], $result);

        $fetchResult['count'] = $fetchResult['count'] + 1;

        return $fetchResult;
    }

    public function downloadS3UrlForBatchOrFileStore(string $id, string $batchOrFileStore)
    {
        if($id === "batch_C7e2YqUIpZ2KwZ")
            return "www.s3.download.com";

        throw new ServerNotFoundException("Batch Id Not Found", ErrorCode::SERVER_ERROR_BATCH_SERVICE_NOT_FOUND);
    }

    public function getResponseFromBatchService(string $relativeUrl)
    {
        if (strpos($relativeUrl, 'C7e2YqUIpZ2KwZ') !== false) {
            throw new \Exception("Service Down Exception");
        }

        $body =  [
            'id'            => 'C3fzDCb4hA4F6b',
            'created_at'    => 1551782255,
            'updated_at'    => 1551782255,
            'entity_id'     => '10000000000000',
            'name'          => null,
            'batch_type_id' => "payment_link",
            'is_scheduled'  => false,
            'upload_count'  => 0,
            'total_count'   => 4,
            'processed_count' => 4,
            'success_count' => 0,
            'failure_count' => 0,
            'attempts'      => 0,
            'status'        => 'COMPLETED',
            'settings'      => null,
            'amount'        => 3799,
            'processed_amount' => 0
        ];

            $response = new Response(200, ['X-Foo' => 'Bar'], json_encode($body), '1.1');

            return $response;
    }
}
