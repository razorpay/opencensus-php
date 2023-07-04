<?php


namespace Unit\Models\Merchant\Methods;


use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\CrossBorderCommonUseCases;
use RZP\Models\Merchant\Attribute\Type;
use RZP\Models\Merchant\Attribute\Group;
use RZP\Models\Merchant\Attribute\Service;
use RZP\Models\Merchant\Methods\Core as MethodsCore;

class CoreTest extends TestCase
{
    public function getMerchantMethodsFixture($upiEnabled, $inAppUPIEnabled, $merchantId, $intlbankTransferModes = [])
    {
        $methods = [
            'upi'           => $upiEnabled,
            'merchant_id'   => $merchantId,
            'disabled_banks'=> [],
            'banks'         => '[]',
            'addon_methods' => [
                'upi' => [
                    'in_app' => $inAppUPIEnabled
                ],
                'intl_bank_transfer' => $intlbankTransferModes,
            ]
        ];

        $this->fixtures->create('merchant', ['id' => $merchantId]);
        return $this->fixtures->create('methods', $methods);
    }

    protected function mockMozartForMasterCard()
    {
        $mozartServiceMock = $this->getMockBuilder(\RZP\Services\Mock\Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->will($this->returnCallback(
                              function ($namespace,$gateway,$action,$data)
                              {
                                  if($action === 'merchant_enrollment')
                                  {
                                      return [
                                          'data' => [
                                              "merchantData" =>[
                                                  [
                                                      "status"     => "Successful",
                                                      "merchantID" => $data['merchantData']['merchantID'],
                                                  ]
                                              ]
                                          ]
                                      ];
                                  }
                              }
                          ));

    }

    public function testOnboardMerchantOnNetworks()
    {
        $payload = [
            'merchant_id' => '10000000000000',
            'networks'    => [
                'mastercard',
                'visa'
            ]
        ];

        $this->repo = $this->app['repo'];

        $this->mockMozartForMasterCard();

        $result  = (new Service())->onboardMerchantOnNetworks($payload);

        $this->assertEquals('successful', $result['status']);
        $this->assertEquals('10000000000000', $result['merchant_id']);

        $attribute = $this->repo->merchant_attribute->getValueForProductGroupType("10000000000000",Product::PRIMARY, Group::MASTERCARD, Type::REQUESTER_ID);
        $this->assertNotEmpty($attribute);

        $attribute = $this->repo->merchant_attribute->getValueForProductGroupType("10000000000000",Product::PRIMARY, Group::MASTERCARD, Type::MERCHANT_NAME);
        $this->assertNotEmpty($attribute);

        $attribute = $this->repo->merchant_attribute->getValueForProductGroupType("10000000000000",Product::PRIMARY, Group::VISA, Type::REQUESTER_ID);
        $this->assertNotEmpty($attribute);

        $attribute = $this->repo->merchant_attribute->getValueForProductGroupType("10000000000000",Product::PRIMARY, Group::VISA, Type::MERCHANT_NAME);
        $this->assertNotEmpty($attribute);
    }

    public function testUpiIsEnabled()
    {
        $expected = [
            'entity' => 'methods',
            'upi' => true,
        ];
        $methods = $this->getMerchantMethodsFixture(true, 0,'8vUslVi0uFOSoy');

        $upiMethod = (new MethodsCore())->getUpiMethodForMerchant($methods->merchant);
        $this->assertEquals($upiMethod, $expected);
    }

    public function testUpiIsDisabled()
    {
        $expected = [
            'entity' => 'methods',
            'upi' => false,
        ];
        $methods = $this->getMerchantMethodsFixture(false, 0,'5ohNv7JkUtGrRx');

        $upiMethod = (new MethodsCore())->getUpiMethodForMerchant($methods->merchant);
        $this->assertEquals($upiMethod, $expected);
    }

    public function testInAppUpiIsEnabled()
    {
        $methods = $this->getMerchantMethodsFixture(true, 1,'8vUslVi0uFOSoy');

        $data = (new MethodsCore())->getFormattedMethods($methods->merchant);
        $this->assertEquals($data['in_app'], 1);
    }

    public function testIntlBankTransferACHIsEnabled()
    {
        $intlBankTransferModes = [
          'ach' => 1,
        ];
        $methods = $this->getMerchantMethodsFixture(true, 1,'8vUslVi0uFOSoy', $intlBankTransferModes);

        $data = (new MethodsCore())->getFormattedMethods($methods->merchant);
        $this->assertEquals(1,$data['intl_bank_transfer']['usd']);

    }

    public function testIntlBankTransferSWIFTIsEnabled()
    {
        $intlBankTransferModes = [
            'swift' => 1,
        ];
        $methods = $this->getMerchantMethodsFixture(true, 1,'8vUslVi0uFOSoy', $intlBankTransferModes);

        $data = (new MethodsCore())->getFormattedMethods($methods->merchant);
        $this->assertEquals(1,$data['intl_bank_transfer']['swift']);
    }

    public function testIntlBankTransferACHANDSWIFTIsEnabled()
    {
        $intlBankTransferModes = [
            'ach' => 1,
            'swift' => 1,
        ];
        $methods = $this->getMerchantMethodsFixture(true, 1,'8vUslVi0uFOSoy', $intlBankTransferModes);

        $data = (new MethodsCore())->getFormattedMethods($methods->merchant);
        $this->assertEquals(1,$data['intl_bank_transfer']['usd']);
        $this->assertEquals(1,$data['intl_bank_transfer']['swift']);
    }

    public function testSodexoIsEnabled()
    {
        $methods = [
            'card'          => true,
            'merchant_id'   => '8vUslVi0uFOSoy',
            'disabled_banks'=> [],
            'banks'         => '[]',
            'addon_methods' => [
                'card' => [
                    'sodexo' => true,
                ]
            ]
        ];

        $this->fixtures->create('merchant', ['id' => '8vUslVi0uFOSoy']);
        $methods = $this->fixtures->create('methods', $methods);

        $data = (new MethodsCore())->getFormattedMethods($methods->merchant);

        $this->assertTrue($data['sodexo']);
    }


}
