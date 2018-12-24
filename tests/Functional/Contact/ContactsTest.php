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

    public function testCreateContactInvalidReferenceId()
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

    public function testFetchContactByAccountNumber()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888']);

        $this->createFundAccount($contact->getPublicId());

        $this->startTest();
    }

    public function testFetchContactByFundAccountId()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888']);

        $fundAccount = $this->createFundAccount($contact->getPublicId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/contacts?fund_account_id=' . $fundAccount['id'];

        $this->startTest($data);
    }

    public function testUpdateContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self', 'reference_id' => '213']);

        $this->startTest();
    }

    public function testDeleteContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    protected function createFundAccount($contactId)
    {
        $testdata = [
            'request' => [
                'url' => '/fund_accounts',
                'method' => 'post',
                'content' => [
                    'account_type' => "bank_account",
                    'contact_id'   => $contactId,
                    'details' => [
                        'beneficiary_name' => "test",
                        'ifsc_code' => 'SBIN0007105',
                        'account_number' => '111000',
                    ],
                ],
            ],
            'response' => [
                'content' => [
                ],
            ],
        ];

        return $this->runRequestResponseFlow($testdata);
    }
}
