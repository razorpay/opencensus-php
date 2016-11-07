<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class RoleTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RoleData.php';

        parent::setUp();

        $this->orgId = '6dLbNSpv5XbCOF';
    }

    public function testCreateRole()
    {
        // TODO inform giri to remove dependency with id
        $org = $this->fixtures->create('org', ['id' => $this->orgId]);

        $this->ba->appAuth();

        return $this->startTest();
    }

    public function testGetRole()
    {
        $role = $this->testCreateRole();

        $testData = $this->testData['testGetRole'];

        $testData['request']['url'] = $testData['request']['url'].'/'.$role['id'];

        $this->ba->appAuth();

        $result = $this->startTest($testData);
    }
}
