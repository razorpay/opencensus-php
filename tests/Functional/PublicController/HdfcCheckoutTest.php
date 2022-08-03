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
}
