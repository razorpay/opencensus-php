<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ContactsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/ContactsTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testGetContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testFetchContacts()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'name' => 'Contact Y']);

        $this->startTest();
    }

    public function testFetchContactsByEmail()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'email' => 'test@test1.com']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'email' => 'random@test.com']);

        $this->startTest();
    }

    public function testCreateContact()
    {
        $this->startTest();
    }

    public function testCreateContactWithoutName()
    {
        $this->startTest();
    }

    public function testCreateContactInvalidName()
    {
        $this->startTest();
    }

    public function testCreateContactInvalidType()
    {
        $this->startTest();
    }

    public function testFetchContactsByPhone()
    {
        $this->fixtures->create('contact', ['id' => '1000004contact', 'email' => 'test@test4.com', 'contact' => '8888888888']);

        $this->startTest();
    }

    public function testFetchContactsByName()
    {
        $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test4.com', 'contact' => '8888888888', 'name' => 'testContact']);

        $this->startTest();
    }

    public function testUpdateContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self']);

        $this->startTest();
    }

    public function testDeleteContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }
}
