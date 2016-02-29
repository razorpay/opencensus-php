<?php

namespace Tests\Functional\UserMethod;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class UserMethodTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/UserTestData.php';

        parent::setUp();

        $user = $this->fixtures->create('user:default_users');

        $card = $this->fixtures->create('card', ['id' => '10000savedcard']);

        $userMethods = $this->fixtures->create('user_method:default_user_methods');
    }


    public function testAddUserMethodCard()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddUserMethodWallet()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddUserMethodNetbanking()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetUserMethods()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetUserMethod()
    {
        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testDeleteUserMethod()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }
}