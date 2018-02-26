<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Request;

class MerchantRequest extends Base
{
    const DEFAULT_MERCHANT_REQUEST_ID   = 'mrId1000000000';
    const DEFAULT_MERCHANT_REQUEST_NAME = 'marketplace';
    const DEFAULT_MERCHANT_REQUEST_TYPE = Request\Type::PRODUCT;
    const DEFAULT_MERCHANT_ID           = '10000000000000';

    public function setUp()
    {
        $this->fixtures->create('merchant_request:default_merchant_request');
    }

    public function createDefaultMerchantRequest()
    {
        //$merchant = $this->fixtures->create('merchant:with_keys');

        $merchantRequest = $this->fixtures->create('merchant_request', [
            'id'          => self::DEFAULT_MERCHANT_REQUEST_ID,
            'merchant_id' => self::DEFAULT_MERCHANT_ID,//$merchant['id'],
            'name'        => self::DEFAULT_MERCHANT_REQUEST_NAME,
            'status'      => Request\Status::UNDER_REVIEW,
            'type'        => self::DEFAULT_MERCHANT_REQUEST_TYPE,
        ]);

        $this->fixtures->create('state', [
            'entity_id'   => $merchantRequest->getId(),
            'entity_type' => 'merchant_request',
            'name'        => Request\Status::UNDER_REVIEW,
            'merchant_id' => self::DEFAULT_MERCHANT_ID,//$merchant['id'],
        ]);
    }
}
