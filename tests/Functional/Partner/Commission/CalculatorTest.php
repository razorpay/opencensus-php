<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Models\Partner\Commission\Calculator;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\Commission\Base;

class CalculatorTest extends OAuthTestCase
{
    use OAuthTrait;

    private $ruleEngine;

    public function setUp()
    {
        parent::setUp();

        include_once __DIR__ . "/Base/Engine.php";

        $this->ruleEngine = new Base\Engine($this->fixtures);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->privateAuth();
    }

    public function testImplicitVariable()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testInvalidSource()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPartnerDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPartnerConfigDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitPricingDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPostpaidFeeModel()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testCommissionDisabled()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }
}
