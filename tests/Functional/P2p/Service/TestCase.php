<?php

namespace RZP\Tests\P2p\Service;

use Carbon\Carbon;
use RZP\Tests\Functional;
use RZP\Models\P2p\Base\MorphMap;
use RZP\Tests\P2p\Service\Base\Traits;

class TestCase extends Functional\TestCase
{
    const DEVICE_1 = 'device_1';
    const DEVICE_2 = 'device_2';

    use Traits\MockSdkTrait;
    use Traits\AssertionTrait;
    use Traits\ExceptionTrait;
    use Traits\MockServerTrait;
    use Traits\DbEntityFetchTrait;

    protected $gateway = null;

    /**
     * Each Gateway Implementation will have its own device set map
     * @var array
     */
    protected $deviceSetMap = [];

    /**
     * @var $fixtures Base\Fixtures\Fixtures
     */
    protected $fixtures;

    /**
     * @var $exceptionHandler Base\MockExceptionHandler
     */
    protected $exceptionHandler;

    /**
     * @var Carbon
     */
    protected $testCurrentTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerMockExceptionHandler();

        $this->fixtures = new Base\Fixtures\Fixtures($this->deviceSetMap);

        $this->resetMockServer();

        $this->testCurrentTime = Carbon::now();

        $this->setDeviceTokenExpiryValidation(false);

        MorphMap::boot();
    }

    protected function tearDown(): void
    {
        $this->now($this->testCurrentTime);

        $this->checkForMockedActions();

        parent::tearDown();
    }

    protected function getCustomerHelper(): Base\CustomerHelper
    {
        return new Base\CustomerHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getDeviceHelper(): Base\DeviceHelper
    {
        return new Base\DeviceHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getClientHelper(): Base\ClientHelper
    {
        return new Base\ClientHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getBankAccountHelper(): Base\BankAccountHelper
    {
        return new Base\BankAccountHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getVpaHelper(): Base\VpaHelper
    {
        return new Base\VpaHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getBeneficiaryHelper(): Base\BeneficiaryHelper
    {
        return new Base\BeneficiaryHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getTransactionHelper(): Base\TransactionHelper
    {
        return new Base\TransactionHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getMandateHelper(): Base\MandateHelper
    {
        return new Base\MandateHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getBlackListHelper(): Base\BlackListHelper
    {
        return new Base\BlackListHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function getPreferencesHelper(): Base\PreferencesHelper
    {
        return new Base\PreferencesHelper($this->fixtures, $this->exceptionHandler);
    }

    protected function registerMockExceptionHandler()
    {
        $this->exceptionHandler = $this->app->make(Base\MockExceptionHandler::class);

        $this->app->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class,
            function()
            {
                return $this->exceptionHandler;
            });
    }

    protected function now($now = null): Carbon
    {
        Carbon::setTestNow($now);

        return Carbon::now();
    }

    protected function setDeviceTokenExpiryValidation(bool $value)
    {
        $this->app['p2p.ctx']->setShouldRefreshDeviceToken($value);
    }

    /**
     * Will clone if the $item is Object, else returns the $item itself
     * Also runs a closure on cloned item
     *
     * @param $item
     * @param $closure
     * @return mixed
     */
    protected function clone($item, $closure)
    {
        if (is_object($item))
        {
            $item = clone $item;
        }

        $closure($item);

        return $item;
    }
}
