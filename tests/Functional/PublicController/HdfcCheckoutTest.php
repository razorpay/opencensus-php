<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Models\Admin\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use Illuminate\Support\Facades\View;

class HdfcCheckoutTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/HdfcCheckoutTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testHdfcCheckoutHit()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000',
            [
                'org_id'      =>  $org->getId(),
            ]
        );

        $this->generateViewMocks($org->getCustomCode());

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['hdfc_checkout_2']);

        $this->startTest();
    }

    public function testHdfcCheckoutHitWithCCText()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000',
            [
                'org_id'      =>  $org->getId(),
            ]
        );

        $this->generateViewMocksWithCC($org->getCustomCode());

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['hdfc_checkout_2',"rmv_cc_text_from_logo"]);

        $this->startTest();
    }

    public function testHdfcCheckoutNotHit()
    {
        $this->generateViewMocks('');

        $this->startTest();
    }
    protected function generateViewMocks($customCode)
    {
        $arr = [
            'key' => 'rzp_test_TheTestAuthKey',
            'options' => '{"receiver_types":"qr_code"}',
            'meta' => '{"custom_code":"rzp","checkout_logo_url":null,"custom_checkout_logo_enabled":false}',
            'script' => 'https://cdn.razorpay.com/static/hosted/embedded-entry.js',
            'urls' => '{}'
        ];
        $resp = ['type' => 'not_hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);

        // For HDFC Checkout 2.0 case
        $arr['script'] = 'https://cdn.razorpay.com/static/hosted/standard-vas.js';
        $arr['meta'] = '{"type":"hdfcvas","custom_code":"'.$customCode.'","checkout_logo_url":null,"custom_checkout_logo_enabled":false}';
        $resp = ['type' => 'hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);
    }
    protected function generateViewMocksWithCC($customCode)
    {
        $arr = [
            'key' => 'rzp_test_TheTestAuthKey',
            'options' => '{"receiver_types":"qr_code","order_id":"order_PDHE9FEX1vAlDG","method":{"smartcollect":true}}',
            'meta' => '{"type":"hdfcvas","custom_code":"'.$customCode.'","checkout_logo_url":null,"custom_checkout_logo_enabled":false,"rmv_cc_text_from_logo":true}',
            'script' => 'https://cdn.razorpay.com/static/hosted/standard-vas.js',
            'urls' => '{}'
        ];
        $resp = ['type' => 'hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);

        // For HDFC Checkout 2.0 case
        $arr['options'] = '{"receiver_types":"qr_code"}';
        $resp = ['type' => 'not_hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);
    }
    public function testHdfcCheckoutDisabledByFeatureFlag()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000',
            [
                'org_id'      =>  $org->getId(),
            ]
        );

        $this->generateViewMocksForDisabledCheckout($org->getCustomCode());

        $this->ba->publicAuth();

        // Enable HDFC Checkout 2 feature
        $this->fixtures->merchant->addFeatures(['hdfc_checkout_2']);

        // Enable the disable flag - this should prevent standard-vas.js from being used
        $this->fixtures->merchant->addFeatures(['disable_checkoutv2_hosted']);

        $this->startTest();
    }

    protected function generateViewMocksForDisabledCheckout($customCode)
    {
        // When DSBL_CHKOUTV2_HOSTED is enabled, it should use embedded-entry.js instead of standard-vas.js
        $arr = [
            'key' => 'rzp_test_TheTestAuthKey',
            'options' => '{"receiver_types":"qr_code"}',
            'meta' => '{"type":"hdfcvas","custom_code":"'.$customCode.'","checkout_logo_url":null,"custom_checkout_logo_enabled":false}',
            'script' => 'https://cdn.razorpay.com/static/hosted/embedded-entry.js',
            'urls' => '{}'
        ];
        $resp = ['type' => 'not_hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);
    }
}
