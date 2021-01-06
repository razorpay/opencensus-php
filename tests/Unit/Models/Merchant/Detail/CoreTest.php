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
            ->setMethods(['getMerchantRiskFactor'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantRiskFactor')
            ->willReturn(['impersonated' => true]);

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

        $this->assertEquals(ActivationFlow::GREYLIST, $core->getActivationFlow($merchant, $merchantDetails ,null,false));
    }

    public function testGreylistOnMRSImpersonatedFalse()
    {
        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantRiskFactor'])
            ->getMock();

        $mockMR->expects($this->once())
            ->method('getMerchantRiskFactor')
            ->willReturn(['impersonated' => false]);

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

        $this->assertEquals(ActivationFlow::WHITELIST, $core->getActivationFlow($merchant, $merchantDetails ,null,false));
    }
}
