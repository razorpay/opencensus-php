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

        $this->createEsMockAndSetExpectations(__FUNCTION__);

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

    public function testFetchContactsByNameActiveAndType()
    {
        $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test4.com', 'contact' => '8888888888', 'name' => 'Test Contact']);

        $this->createEsMockAndSetExpectations(__FUNCTION__);

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

    public function testFetchContactByActive()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888', 'active' => 1]);

        $fundAccount = $this->createFundAccount($contact->getPublicId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/contacts?active=1';

        $this->startTest($data);
    }

    public function testFetchContactByType()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888', 'active' => 1, 'type' => 'customer']);

        $fundAccount = $this->createFundAccount($contact->getPublicId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/contacts?type=customer';

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

    public function testBulkContact()
    {
        // $this->markTestSkipped();

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkContactWithInvalidContactId()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkContactWithValidContactId()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);

        $this->startTest();
    }

    public function testBulkContactWithSameIdempotencyKey()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);

        $this->startTest();
    }

    protected function createFundAccount($contactId)
    {
        $testdata = [
            'request'  => [
                'url'     => '/fund_accounts',
                'method'  => 'post',
                'content' => [
                    'account_type' => "bank_account",
                    'contact_id'   => $contactId,
                    'bank_account'      => [
                        'name'           => "test",
                        'ifsc'           => 'SBIN0007105',
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
