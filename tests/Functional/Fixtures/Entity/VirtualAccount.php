<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\BharatQr\Constants;
use RZP\Models\Merchant\Account;

class VirtualAccount extends Base
{
    public function createDefaultVirtualAccountAndQr()
    {
        $qrCodeAttributes =
            [
                'id'                    => Constants::SHARED_QR_CODE,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'provider'              => 'bharat_qr',
                'entity_id'             => Constants::SHARED_VIRTUAL_ACCOUNT,
                'entity_type'           => 'virtual_account',
                'qr_string'             => '92815729834574938',
                'short_url'             => 'random_url',
                'created_at'            => time(),
                'updated_at'            => time(),
            ];

        $this->fixtures->create('qr_code', $qrCodeAttributes);

       $attributes =
           [
               'id'                    => Constants::SHARED_VIRTUAL_ACCOUNT,
               'merchant_id'           => Account::TEST_ACCOUNT,
               'status'                => 'active',
               'name'                  => 'Shared Virtual Account',
               'qr_code_id'            => Constants::SHARED_QR_CODE,
               'notes'                 =>
                    [
                        'a' => 1
                    ],
           ];

       $this->fixtures->create('virtual_account', $attributes);
    }
}
