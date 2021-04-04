<?php

namespace RZP\Tests\Unit\VendorPayment;

use Mockery;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class VendorPaymentTest extends TestCase
{
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    public function testVendorPaymentUpdateIsCalledWhenFeatureIsEnabled()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('vendor-payment', $vpMock);

        $payout = $this->fixtures->create('payout', [
            'status'            =>      'processed',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',

        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'vdpm_1',
            'source_type' => 'vendor_payments',
            'priority' => 1
        ]);

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $vpMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }

    public function testPayoutStatusPushForTaxPaymentAsSource()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('vendor-payment', $vpMock);

        $payout = $this->fixtures->create('payout', [
            'status'            =>      'processed',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'vdpm_1',
            'source_type' => 'tax_payments',
            'priority' => 1
        ]);

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $vpMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }

    public function testVendorPaymentPushSkippedWhenSourceDetailsNotPresent()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('vendor-payment', $vpMock);

        $payout = $this->fixtures->create('payout', [
            'status' => 'processed',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'vdpm_1',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $vpMock->shouldNotHaveReceived('pushPayoutStatusUpdate');
    }
}
