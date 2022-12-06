<?php


namespace Unit\Services;

use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\TestCase;


class SalesforceClientTest extends TestCase
{

    /** @var $salesforceClient SalesForceClient  */
    protected $salesforceClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.salesforce.mock', true);

        $this->salesforceClient = $this->app['salesforce'];
    }

    public function assertBooleanValuesAsInt(array $payload)
    {
        // For some boolean fields, it is required to send it as int(1/0)
        $fieldsBooleanNotAllowed = ['business_banking', 'activated', 'submitted'];
        foreach ($fieldsBooleanNotAllowed as $field)
        {
            if (isset($payload[$field]))
            {
                $this->assertIsInt($payload[$field]);
            }
        }
    }

    public function testPayloadForPreSignUpDetails()
    {
        $merchant = $this->fixtures->create('merchant');

        $input = [
            'business_name'      => 'Dummy Business Name',
            'business_type'      => 'Dummy Business Type',
            'contact_mobile'     => '9876556789',
            'transaction_volume' => '100000',
            'website'            => 'www.dummy.com',
            'first_utm_campaign' => 'xyx',
            'first_utm_medium'   => 'abc',
            'first_utm_source'   => 'def',
            'final_utm_medium'   => 'random',
            'final_utm_source'   => 'random_source',
            'final_utm_campaign' => 'random_campaign',
            'final_page'         => 'www.random.com',
        ];

        $payload = $this->salesforceClient->payloadGenerationForPreSignupDetails($input, $merchant);

        $this->assertBooleanValuesAsInt($payload);
    }

    public function testPayloadForPrimaryMerchantInterestInBanking()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => $merchant->getId(),
            'business_name'  => 'Dummy Business Name',
            'contact_name'   => 'Dummy contact Name',
            'contact_mobile' => 'Dummy contact mobile'
        ]);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchant->getId(),
                'product'     => 'banking',
                'group'       => 'onboarding',
                'type'        => 'merchant_onboarding_category',
                'value'       => 'self_serve'
            ]);

        $payload = $this->salesforceClient->payloadGenerationForInterestOfPrimaryMerchantInBanking($merchant);

        $this->assertEquals('Dummycontactmobile', $payload[0]['contact_mobile']);

        $this->assertBooleanValuesAsInt($payload);
    }
}
