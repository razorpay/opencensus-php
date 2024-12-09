<?php

namespace RZP\Tests\Functional\Settlement\Transfer;

use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class EntityTest extends TestCase
{
    use DbEntityFetchTrait;

    public function testGetMerchantAttribute()
    {

        $merchant = $this->fixtures->create("merchant");

        $transfer  = $this->fixtures->create("settlement_transfer" , [
            "merchant_id" => $merchant->getId()
        ]);

        $settlementTransfer = $this->getDbEntityById('settlement_transfer', $transfer->getId());

        $this->assertNotNull($settlementTransfer->merchant);
        $this->assertTrue($settlementTransfer->relationLoaded('merchant'));
    }

    public function testGetSourceMerchantAttribute()
    {

        $merchant = $this->fixtures->create("merchant");

        $transfer  = $this->fixtures->create("settlement_transfer" , [
            "source_merchant_id" => $merchant->getId()
        ]);

        $settlementTransfer = $this->getDbEntityById('settlement_transfer', $transfer->getId());

        $this->assertEquals($settlementTransfer->getSourceMerchantId(), $merchant->getId());

        $this->assertNotNull($settlementTransfer->sourceMerchant);
        $this->assertTrue($settlementTransfer->relationLoaded('sourceMerchant'));
    }
}
