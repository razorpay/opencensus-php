<?php

namespace RZP\Tests\Unit\VendorPayment;

use Mockery;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payout\SourceUpdater;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class VendorPaymentTest extends TestCase
{
    use TestsBusinessBanking;

    public function setUp()
    {
        parent::setUp();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    public function testVendorPaymentUpdateIsCalledWhenFeatureIsEnabled()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('vendor-payment', $vpMock);

        $payout = $this->fixtures->create('payout');

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $vpMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }
}
