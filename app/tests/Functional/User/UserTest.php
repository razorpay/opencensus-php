<?php

namespace Tests\Functional\User;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class UserTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/UserTestData.php';

        parent::setUp();

        $user = $this->fixtures->create('user:default_users');
    }

    public function testCreateUser()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $user = $this->getLastEntity('user', true);

        assert($user !== null);
    }

    public function testUpdateUser()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetUser()
    {
        $this->ba->privateAuth();

        $this->startTest();        
    }

    public function testDeleteUser()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }
}