<?php

namespace RZP\Tests\P2p\Service;

use RZP\Tests\Functional;
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

    public function setUp()
    {
        parent::setUp();

        $this->fixtures = new Base\Fixtures\Fixtures($this->deviceSetMap);

        $this->resetMockServer();
    }

    public function tearDown()
    {
        $this->checkForMockedActions();

        parent::tearDown();
    }

    protected function getCustomerHelper(): Base\CustomerHelper
    {
        return new Base\CustomerHelper($this->fixtures);
    }

    protected function getDeviceHelper(): Base\DeviceHelper
    {
        return new Base\DeviceHelper($this->fixtures);
    }

    protected function getBankAccountHelper(): Base\BankAccountHelper
    {
        return new Base\BankAccountHelper($this->fixtures);
    }

    protected function getVpaHelper(): Base\VpaHelper
    {
        return new Base\VpaHelper($this->fixtures);
    }

    protected function getBeneficiaryHelper(): Base\BeneficiaryHelper
    {
        return new Base\BeneficiaryHelper($this->fixtures);
    }

    protected function getTransactionHelper(): Base\TransactionHelper
    {
        return new Base\TransactionHelper($this->fixtures);
    }
}
