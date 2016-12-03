<?php

namespace RZP\Tests\Functional\Admin\AuthPolicy;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use RZP\Models\Admin\Admin;

class AuthPolicyTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AuthPolicyData.php';

        parent::setUp();
    }

    public function testAdminLogin()
    {
        $this->ba->appAuth('rzp_live');

        $this->startTest();
    }

    public function testWeakPassword()
    {
        $this->ba->adminAuth();

        $org = $this->createOrg();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testShortPassword()
    {
        $this->ba->adminAuth();

        $org = $this->createOrg();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testLongPassword()
    {
        $this->ba->adminAuth();

        $org = $this->createOrg();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    protected function createOrg()
    {
        return $this->fixtures->create('org', [
                    'email'         => 'random@rzp.com',
                    'email_domains' => 'rzp.com',
        ]);
    }
}