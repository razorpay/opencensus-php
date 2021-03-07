<?php


namespace Unit\Models\Merchant\Detail;


use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use \RZP\Models\Merchant\Core as MerchantCore;
use RZP\Services\MerchantRiskClient;
use \RZP\Tests\Functional\TestCase;

class CoreTest extends TestCase
{
    public function testGreylistOnMRSImpersonatedTrue()
    {
        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantRiskScores'])
            ->getMock();

        $mockMR->expects($this->exactly(0))
            ->method('getMerchantRiskScores')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id" => "Ede1NCc0fb1pum",
                "fields" => [
                    [
                        "key" => "website",
                        "list" => "blacklist",
                        "score" => "100"
                    ]
                ]
            ]);

        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(0))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $core = new DetailCore();

        $core->setMerchantRiskClient($mockMR);

        $core->setMerchantCoreForRazorx($mockMC);

        $merchant = $this->fixtures->merchant->create();

        $merchantDetails = $this->fixtures->merchant_detail->create(['business_category' => 'financial_services', 'business_subcategory' => 'accounting']);

        $merchant->merchantDetail = $merchantDetails;

        $this->assertEquals(ActivationFlow::GREYLIST, ActivationFlow::GREYLIST);
    }

    public function testGreylistOnMRSImpersonatedFalse()
    {
        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantImpersonatedDetails'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantImpersonatedDetails')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id" => "Ede1NCc0fb1pum"
            ]);

        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->once())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $core = new DetailCore();

        $core->setMerchantRiskClient($mockMR);

        $core->setMerchantCoreForRazorx($mockMC);

        $merchant = $this->fixtures->merchant->create();

        $merchantDetails = $this->fixtures->merchant_detail->create(['business_category' => 'financial_services', 'business_subcategory' => 'accounting']);

        $merchant->merchantDetail = $merchantDetails;

        $this->assertEquals(ActivationFlow::WHITELIST, $core->getActivationFlow($merchant, $merchantDetails ,null,false));
    }

    public function testDedupeBeingSkippedForLinkedAccount()
    {
        $core = new DetailCore();

        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->assertEquals(false, $core->isDedupeAllowed($linkedAccount));
    }
}
