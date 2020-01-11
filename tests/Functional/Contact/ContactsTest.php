<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Services\RazorXClient;
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

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchContacts()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'name' => 'Contact Y']);

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

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

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testContactsWithExpiredKey()
    {
        $this->fixtures->key->edit('TheTestAuthKey', ['expired_at' => time()]);

        $data = $this->testData[__FUNCTION__];

        // Create Contact
        $data['request']['url'] = '/contacts';
        $data['request']['method'] = 'POST';

        $this->startTest($data);

        // Fetch Contacts
        $data['request']['url'] = '/contacts';
        $data['request']['method'] = 'GET';

        $this->startTest($data);

        // GET Contact
        $data['request']['url'] = '/contacts/1000000contact';
        $data['request']['method'] = 'GET';

        $this->startTest($data);

        // GET Contact
        $data['request']['url'] = '/contacts/1000000contact';
        $data['request']['method'] = 'PATCH';

        $this->startTest($data);
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

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testDeleteContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testDuplicateContactCreationOnApi()
    {
        $this->testCreateContact();

        $contact = $this->getLastEntity('contact', true);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals($response['id'], $contact['id']);
    }

    public function testDuplicateContactCreationOnDashboard()
    {
        $this->testCreateContact();

        $contact = $this->getLastEntity('contact', true);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEquals($response['id'], $contact['id']);
    }

    public function testDuplicateContactCreationWithSameName()
    {
        $this->testCreateContact();

        $contact = $this->getLastEntity('contact', true);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertNotEquals($response['id'], $contact['id']);
    }

    public function testDuplicateContactCreationWithSameNameAndEmptyAttributes()
    {
        $request  = [
            'content' => [
                'name'         => 'Test / Contact',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $contact = $this->getLastEntity('contact', true);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertNotEquals($response['id'], $contact['id']);
    }

    public function testDuplicateContactWithEmptyValuesOfSomeAttributes()
    {
        $request  = [
            'content' => [
                'name'         => 'Test / Contact',
                'contact'      => null
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $contact1 = $this->getLastEntity('contact', true);

        $request  = [
            'content' => [
                'name'         => 'Test / Contact',
                'contact'      => ''
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ];

        $this->makeRequestAndGetContent($request);

        $contact2 = $this->getLastEntity('contact', true);

        $this->assertEquals($contact1['id'], $contact2['id']);
    }

    public function testDoNotAllowDuplicateChecksInContactCreation()
    {
        $this->testCreateContact();

        $contact = $this->getLastEntity('contact', true);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getTreatment'])
                            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('create_duplicate');

        $this->ba->privateAuth();

        $request =  [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals($contact['id'], $response['id']);
    }

    public function testCreateContactWithoutType()
    {
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
                'status_code' =>201
            ],
        ];

        return $this->runRequestResponseFlow($testdata);
    }
}
