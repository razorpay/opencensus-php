<?php

namespace RZP\Tests\P2p\Service;

use RZP\Tests\Functional;
use RZP\Tests\P2p\Service\Base\Traits;

class TestCase extends Functional\TestCase
{
    use Traits\AssertionTrait;
    use Traits\ExceptionTrait;
    use Traits\DbEntityFetchTrait;

    /**
     * @var $fixtures Base\Fixtures\Fixtures
     */
    protected $fixtures;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures = new Base\Fixtures\Fixtures();
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
