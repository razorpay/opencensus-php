<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Base\EsDao;
use RZP\Models\Merchant\Website\Constants;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MerchantGetPolicyDetailsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantPolicyDetailsTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();
    }
    public function testGetMerchantPolicyDetails(): void
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantId='10000000000000';

        $this->fixtures->edit('merchant',$merchantId, []);

        $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchantId,
            'business_website' => "http://hello.com"
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'           => $merchantId,
            'status'      => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                    "published_url"  => env(Constants::MERCHANT_POLICIES_SUBDOMAIN) . '/compliance/'.$merchantId.'/contact_us'
                ]
            ]
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls' => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $response = $this->startTest();
        $this->assertNotNull($response['url']);
        $this->assertNotNull($response['display_name']);
    }

    protected function mockSplitzTreatment($output): void
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }
}
