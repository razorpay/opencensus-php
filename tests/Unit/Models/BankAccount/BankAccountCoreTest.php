<?php

namespace Unit\Models\BankAccount;

use App;
use Mockery;
use Illuminate\Foundation\Application;
use RZP\Constants\Country;
use RZP\Constants\Mode;
use RZP\Models\BankAccount\Core;
use RZP\Tests\TestCase;
use RZP\Models\Merchant\Entity as MerchantEntity;

class BankAccountCoreTest extends TestCase
{
    protected $app;

    protected $bankAccountCore;


    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::getFacadeRoot();
        $this->bankAccountCore = new Core();
    }
    public function testShouldNotifyViaEmailReturnsTrueForIndianCountryAndNonLinkedAccountInDevEnv()
    {
        // Mock the MerchantEntity object
        $merchant = $this->createMock(MerchantEntity::class);
        // Mock environment and merchant details

        $merchant->expects($this->any())
            ->method('getCountry')
            ->willReturn(Country::IN);

        $merchant->expects($this->any())
            ->method('isLinkedAccount')
            ->willReturn(false);

        $reflectionClass = new \ReflectionClass($this->bankAccountCore);
        $modeProperty = $reflectionClass->getProperty('mode');
        $modeProperty->setAccessible(true);  // Make it accessible
        $modeProperty->setValue($this->bankAccountCore, Mode::TEST);

        $mockApp = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['environment'])
            ->getMock();

        $appProperty = $reflectionClass->getProperty('app');
        $appProperty->setAccessible(true);  // Make it accessible
        $appProperty->setValue($this->bankAccountCore, $mockApp);

        //our method is protected, needs to be accessed by reflection
        $mockApp->expects($this->once())->method('environment')->willReturn('dev');
        $reflectionMethod = new \ReflectionMethod($this->bankAccountCore, 'shouldNotifyViaEmail');
        $reflectionMethod->setAccessible(true); // Make the method accessible

        // Assert that the function returns true
        $this->assertTrue($reflectionMethod->invoke($this->bankAccountCore, $merchant));
    }

    public function testShouldNotifyViaEmailReturnsFalseForCountryMY()
    {
        // Mock the MerchantEntity object
        $merchant = $this->createMock(MerchantEntity::class);
        // Mock environment and merchant details

        $merchant->expects($this->any())
            ->method('getCountry')
            ->willReturn(Country::MY);

        $merchant->expects($this->any())
            ->method('isLinkedAccount')
            ->willReturn(false);

        $reflectionClass = new \ReflectionClass($this->bankAccountCore);
        $modeProperty = $reflectionClass->getProperty('mode');
        $modeProperty->setAccessible(true);  // Make it accessible
        $modeProperty->setValue($this->bankAccountCore, Mode::TEST);

        $mockApp = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['environment'])
            ->getMock();

        $appProperty = $reflectionClass->getProperty('app');
        $appProperty->setAccessible(true);  // Make it accessible
        $appProperty->setValue($this->bankAccountCore, $mockApp);

        //our method is protected, needs to be accessed by reflection
        $mockApp->expects($this->once())->method('environment')->willReturn('dev');
        $reflectionMethod = new \ReflectionMethod($this->bankAccountCore, 'shouldNotifyViaEmail');
        $reflectionMethod->setAccessible(true); // Make the method accessible

        // Assert that the function returns true
        $this->assertFalse($reflectionMethod->invoke($this->bankAccountCore, $merchant));
    }


    public function testShouldNotifyViaEmailReturnsFalseForLinkedAccount()
    {
        // Mock the MerchantEntity object
        $merchant = $this->createMock(MerchantEntity::class);
        // Mock environment and merchant details

        $merchant->expects($this->any())
            ->method('getCountry')
            ->willReturn(Country::MY);

        $merchant->expects($this->any())
            ->method('isLinkedAccount')
            ->willReturn(true);

        $reflectionClass = new \ReflectionClass($this->bankAccountCore);
        $modeProperty = $reflectionClass->getProperty('mode');
        $modeProperty->setAccessible(true);  // Make it accessible
        $modeProperty->setValue($this->bankAccountCore, Mode::TEST);

        $mockApp = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['environment'])
            ->getMock();

        $appProperty = $reflectionClass->getProperty('app');
        $appProperty->setAccessible(true);  // Make it accessible
        $appProperty->setValue($this->bankAccountCore, $mockApp);

        //our method is protected, needs to be accessed by reflection
        $mockApp->expects($this->once())->method('environment')->willReturn('dev');
        $reflectionMethod = new \ReflectionMethod($this->bankAccountCore, 'shouldNotifyViaEmail');
        $reflectionMethod->setAccessible(true); // Make the method accessible

        // Assert that the function returns true
        $this->assertFalse($reflectionMethod->invoke($this->bankAccountCore, $merchant));
    }


}
