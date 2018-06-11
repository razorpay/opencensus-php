<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Request;

class MerchantRequest extends Base
{
    const DEFAULT_MERCHANT_REQUEST_NAME   = 'subscriptions';
    const DEFAULT_MERCHANT_ID             = '10000000000000';
    const DEFAULT_MERCHANT_REQUEST_ID     = 'mrId1000000000';
    const MERCHANT_REQUEST                = 'merchant_request';
    const DEFAULT_MERCHANT_REQUEST_TYPE   = Request\Type::PRODUCT;
    const DEFAULT_MERCHANT_REQUEST_STATUS = Request\Status::UNDER_REVIEW;

    public function setUp()
    {
        $this->fixtures->create('merchant_request:default_merchant_request');

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'submitted'   => true,
            'locked'      => false
        ]);
    }

    public function createDefaultMerchantRequest(array $attributes = []): Request\Entity
    {
        $defaultValues = [
            'id'          => self::DEFAULT_MERCHANT_REQUEST_ID,
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'name'        => self::DEFAULT_MERCHANT_REQUEST_NAME,
            'status'      => self::DEFAULT_MERCHANT_REQUEST_STATUS,
            'type'        => self::DEFAULT_MERCHANT_REQUEST_TYPE,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $merchantRequest = $this->fixtures->create('merchant_request', $attributes);

        $this->fixtures->create('state', [
            'entity_id'   => $merchantRequest->getId(),
            'entity_type' => self::MERCHANT_REQUEST,
            'name'        => self::DEFAULT_MERCHANT_REQUEST_STATUS,
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
        ]);

        return $merchantRequest;
    }
}
