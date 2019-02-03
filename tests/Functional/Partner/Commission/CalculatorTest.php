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

        require __DIR__ . "/Base/Engine.php";

        $this->ruleEngine = new Base\Engine($this->fixtures);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->privateAuth();
    }

    public function testImplicitVariable()
    {
        $account = $this->fixtures->merchant->createAccount('10ModalAccount');

        $this->fixtures->merchant->edit($account->getId(), ['partner_type' => 'reseller']);

        $this->ruleEngine->execute('BptVjGnFv6ITBm');
    }
}
