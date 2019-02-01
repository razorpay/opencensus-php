<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\Commission\Base;

class CalculatorTest extends TestCase
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
//        $account = $this->fixtures->merchant->createAccount('10ModalAccount');
//
//        $this->fixtures->merchant->edit($account->getId(), ['partner_type' => 'reseller']);

        $app = $this->fixtures->merchant->createDummyPartnerApp();

//        $this->ruleEngine->execute('BptVjGnFv6ITBm');
    }
}
