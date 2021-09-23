<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Models\Admin\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use Illuminate\Support\Facades\View;
use RZP\Tests\Traits\MocksRazorx;

class HdfcCheckoutTest extends TestCase
{
    use MocksRazorx;
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
        $this->generateViewMocks();

        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000',
            [
                'org_id'      =>  $org->getId(),
            ]
        );

        $this->mockRazorxTreatmentV2('hdfc_checkout_2', 'on');

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['hdfc_checkout_2']);

        $this->startTest();
    }

    public function testHdfcCheckoutNotHit()
    {
        $this->generateViewMocks();

        $this->startTest();
    }

    protected function generateViewMocks()
    {
        $arr = [
            'key' => 'rzp_test_TheTestAuthKey',
            'options' => '{"receiver_types":"qr_code"}',
            'meta' => '{}',
            'script' => 'https://cdn.razorpay.com/static/hosted/embedded-entry.js',
            'urls' => '{}'
        ];
        $resp = ['type' => 'not_hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);

        // For HDFC Checkout 2.0 case
        $arr['script'] = 'https://cdn.razorpay.com/static/hosted/standard-vas.js';
        $arr['meta'] = '{"type":"hdfcvas"}';
        $resp = ['type' => 'hdfc'];

        View::shouldReceive('make')
            ->with('public.embedded', $arr)
            ->andReturn($resp);
    }
}
