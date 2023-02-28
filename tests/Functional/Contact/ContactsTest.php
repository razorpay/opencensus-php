<?php

namespace RZP\Tests\Functional\Contacts;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use PHPUnit\Framework\Assert;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Core;
use RZP\Models\Feature;
use RZP\Models\Contact\Entity;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Services\Segment\XSegmentClient;
use RZP\Services\SplitzService;
use RZP\Services\VendorPayments\Service;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ContactsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
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

    public function testGetContactDetailsForCheckout(): void
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id' => '1000000contact',
                'email' => 'test@test5.com',
                'contact' => '8888888888',
                'name' => 'eum'
            ]
        );

        $this->createFundAccount($contact->getPublicId());

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testGetContactWithTypeVendorAndPrivateAuth()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'vendor']);

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getVendorByContactId'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('getVendorByContactId')
            ->willReturn(['id' => '1', 'contact_id' => 'cont_1000000contact', 'payment_terms' => 10, 'tds_category' => 1, 'expense_id' => '1', 'gstin' => '22AAAAA0000A1Z5']);

        $this->startTest();
    }

    public function testGetContactWithTypeVendorAndProxyAuth()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'vendor']);

        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getVendorByContactId'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('getVendorByContactId')
            ->willReturn([
                'id'                   => '1',
                'contact_id'           => 'cont_1000000contact',
                'payment_terms'        => 10,
                'tds_category'         => 1,
                'expense_id'           => '1',
                'gstin'                => '22AAAAA0000A1Z5',
                'pan'                  => 'test_pan',
                'vendor_portal_status' => 'INVITED'
            ]);

        $this->startTest();
    }

    public function testGetContactWithTypeVendorAndExternalServiceFailure()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'vendor']);

        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getVendorByContactId'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('getVendorByContactId')
            ->willThrowException(
                new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED)
            );

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

    public function testFetchContactsForXDemo()
    {
        $merchant = $this->fixtures->create('merchant',
            [
                'id'          => 'Hrw2ujXW6LGEk7',
            ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->fixtures->create('contact',
            ['id' => '1000001contact', 'name' => 'Contact X', 'created_at' => 0, 'merchant_id' => $merchant['id']]);

        $this->fixtures->create('contact',
            ['id' => '1000002contact', 'name' => 'Contact Y', 'merchant_id' => $merchant['id']]);

        $this->ba->proxyAuth('rzp_test_Hrw2ujXW6LGEk7', $user->getId());

        $this->startTest();
    }

    public function testFetchContactsWithTypeVendorAndPrivateAuth()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X', 'type' => 'vendor']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'name' => 'Contact Y', 'type' => 'customer']);

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getVendorBulk'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('getVendorBulk')
            ->willReturn(
                [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        ['id' => '2', 'contact_id' => 'cont_1000001contact', 'payment_terms' => 10, 'tds_category' => 1, 'gstin' => '22AAAAA0000A1Z5', 'pan' => 'ABCD']
                    ]
                ]
            );

        $this->startTest();
    }

    public function testFetchContactsWithTypeVendorAndProxyAuth()
    {
        $r = $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X', 'type' => 'vendor', 'gstin' => '22AAAAA0000A1Z5']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'name' => 'Contact Y', 'type' => 'customer']);

        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getVendorBulk'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('getVendorBulk')
            ->willReturn(
                [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'id'                   => '2',
                            'contact_id'           => 'cont_1000001contact',
                            'payment_terms'        => 10,
                            'tds_category'         => 1,
                            'expense_id'           => '1',
                            'gstin'                => '22AAAAA0000A1Z4',
                            'pan'                  => 'test_pan',
                            'vendor_portal_status' => 'INVITED'
                        ]
                    ]
                ]
            );

        $this->startTest();
    }

    public function testFetchContactsWithTypeVendorAndExternalServiceFailure()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X', 'type' => 'vendor']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'name' => 'Contact Y', 'type' => 'customer']);

        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getVendorBulk'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('getVendorBulk')
            ->willThrowException(
                new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED)
            );

        $this->startTest();
    }

    public function testFetchContactsWithEmailsFetchesExactMatchesOnly()
    {
        $this->fixtures->create(
            'contact',
            [
                'id' => '1000005contact',
                'name' => 'Contact1A',
                'email' => 'contact1@test.com',
            ]
        );
        $this->fixtures->create(
            'contact',
            [
                'id' => '1000006contact',
                'name' => 'Contact2B',
                'email' => 'contact2@test.com',
            ]
        );

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testFetchContactsByEmail()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'email' => 'test@test1.com']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'email' => 'random@test.com']);

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testFetchContactsByEmailDifferentDomainSameName()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'email' => 'test@test1.com']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'email' => 'test@test.com']);

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateContact()
    {
        $this->startTest();
    }

    public function testCreateContactWithGstinInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);
        $this->startTest();
    }

    public function testCreateContactWithGstinProxyAuth()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreateContactWithInvalidGstinProxyAuth()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreateContactWithGstinPrivateAuth()
    {
        $request = [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
                'gstin' => '22AAAAA0000A1Z5'
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ];
        $response = $this->makeRequestAndGetContent($request);
        self::assertArrayNotHasKey('gstin', $response);
    }

    public function testFetchContactWithGstinInternalAuth()
    {
        $this->testCreateContactWithGstinInternalAuth();
        /* @var $contact Entity */
        $contact = $this->getDbLastEntity('contact');

        $testData = $this->testData['testFetchContactWithGstinInternalAuth'];
        $testData['request']['url'] = '/contacts_internal/'. $contact->getPublicId();
        $this->testData[__FUNCTION__] = $testData;
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);
        $this->startTest();
    }

    public function testFetchContactWithGstinProxyAuth()
    {
        $this->testCreateContactWithGstinProxyAuth();
        /* @var $contact Entity */
        $contact = $this->getDbLastEntity('contact');

        $testData = $this->testData['testFetchContactWithGstinProxyAuth'];
        $testData['request']['url'] = '/contacts/'. $contact->getPublicId();
        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuthTest();
        $this->startTest();
    }

    public function testFetchContactWithGstinPrivateAuth()
    {
        $this->testCreateContactWithGstinPrivateAuth();
        /* @var $contact Entity */
        $contact = $this->getDbLastEntity('contact');

        $request = [
            'url'     => '/contacts/'.$contact->getPublicId(),
            'method'  => 'GET',
            'content' => []
        ];

        $response = $this->makeRequestAndGetContent($request);

        self::assertArrayNotHasKey('gstin', $response);
        Assert::assertEquals($contact->getPublicId(), $response['id']);
    }

    public function testEditContactWithGstinInternalAuth()
    {
        $this->testCreateContactWithGstinInternalAuth();

        $contact = $this->getDbLastEntity('contact');
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);
        $testData = $this->testData['testEditContactWithGstinInternalAuth'];
        $testData['request']['url'] = '/contacts_internal/'. $contact->getPublicId();
        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testEditContactWithGstinProxyAuth()
    {
        $this->testCreateContactWithGstinProxyAuth();

        $contact = $this->getDbLastEntity('contact');
        $testData = $this->testData['testEditContactWithGstinProxyAuth'];
        $testData['request']['url'] = '/contacts/'. $contact->getPublicId();
        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditContactWithGstinPrivateAuth()
    {
        $this->testCreateContactWithGstinPrivateAuth();
        /* @var $contact Entity */
        $contact = $this->getDbLastEntity('contact');

        $request = [
            'url'     => '/contacts/'.$contact->getPublicId(),
            'method'  => 'PATCH',
            'content' => [
                'gstin' => '22AAAAA0000A1Z6',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        self::assertArrayNotHasKey('gstin', $response);
        Assert::assertEquals($contact->getPublicId(), $response['id']);
    }


    public function testCreateContactWithNbsp()
    {
        $this->startTest();
    }

    public function testCreateContactWithApostrophe()
    {
        $this->startTest();
    }

    public function testCreateContactWithCommaAndSlash()
    {
        $this->startTest();
    }

    public function testCreateContactWithEnDash()
    {
        $this->startTest();
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    public function testCreateContactLiveModeNonKycActivatedNonCaActivated()
    {
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateContactLiveModeKycActivatedNonCaActivated()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateContact'];

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateContactLiveModeNonKycActivatedCaActivated()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateContact'];

        $params = [
            'account_number'        => '2224440041626905',
            'merchant_id'           => '10000000000000',
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156'
        ];

        $this->fixtures->on('live')->create('banking_account', $params);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateContactLiveModeNonKycActivatedIciciCaActivated()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateContact'];

        $attributes = [
            'merchant_id'       => '10000000000000',
            'bas_business_id'   => '10000000000000',
        ];

        $this->fixtures->on('live')->create('merchant_detail', $attributes);

        $this->fixtures->on('live')->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
                'channel'           => 'icici',
            ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateContactLiveModeKycActivatedCaActivated()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateContact'];

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $params = [
            'account_number'        => '2224440041626905',
            'merchant_id'           => '10000000000000',
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156'
        ];

        $this->fixtures->on('live')->create('banking_account', $params);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateContactLiveModeKycActivatedIciciCaActivated()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateContact'];

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $attributes = [
            'merchant_id'       => '10000000000000',
            'bas_business_id'   => '10000000000000',
        ];

        $this->fixtures->on('live')->create('merchant_detail', $attributes);

        $this->fixtures->on('live')->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
                'channel'           => 'icici',
            ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorAndPrivateAuth()
    {
        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willReturn(['id' => '1', 'contact_id' => 'cont_xyz', 'payment_terms' => 10, 'tds_category' => 1, 'gstin' => '22AAAAA0000A1Z5', 'pan' => 'ABCD']);

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorAndProxyAuth()
    {
        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willReturn([
                'id'                   => '1',
                'contact_id'           => 'cont_xyz',
                'payment_terms'        => 10,
                'tds_category'         => 1,
                'expense_id'           => '1',
                'gstin'                => '22AAAAA0000A1Z5',
                'pan'                  => 'test_pan',
                'vendor_portal_status' => 'INVITED'
            ]);

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorExternalServiceFailure()
    {
        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willThrowException(
                new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED)
            );

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorWithoutPaymentTerms()
    {
        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willReturn(['id' => '1', 'contact_id' => 'cont_xyz', 'payment_terms' => 0, 'tds_category' => 1, 'gstin' => '22AAAAA0000A1Z5', 'pan' => 'test_pan']);

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorWithoutTdsCategory()
    {
        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willReturn(['id' => '1', 'contact_id' => 'cont_xyz', 'payment_terms' => 10, 'tds_category' => 0, 'gstin' => '22AAAAA0000A1Z5', 'pan' => 'test_pan']);

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorWithoutGstin()
    {
        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willReturn(['id' => '1', 'contact_id' => 'cont_xyz', 'payment_terms' => 10, 'tds_category' => 1, 'gstin' => null, 'pan' => 'test_pan']);

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorWithoutPan()
    {
        $this->ba->proxyAuth();

        $vendorPaymentServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createVendor'])
            ->getMock();

        $this->app->instance('vendor-payment', $vendorPaymentServiceMock);

        $vendorPaymentServiceMock->expects($this->once())
            ->method('createVendor')
            ->willReturn(['id' => '1', 'contact_id' => 'cont_xyz', 'payment_terms' => 10, 'tds_category' => 1, 'gstin' => '22AAAAA0000A1Z5', 'pan' => null]);

        $this->startTest();
    }

    public function testCreateContactWithTypeVendorWithoutVendorDetails()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateContactWithProxyAuth()
    {
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
        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
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
        $this->fixtures->create(
            'contact',
            [
                'id' => '1000005contact',
                'email' => 'test@test4.com',
                'contact' => '8888888888',
                'name' => 'Test Contact',
            ]
        );

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testFetchContactByAccountNumber()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id' => '1000005contact',
                'email' => 'test@test5.com',
                'contact' => '8888888888',
            ]
        );

        $this->createFundAccount($contact->getPublicId());

        $this->startTest();
    }

    public function testFetchContactByFundAccountId()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id' => '1000005contact',
                'email' => 'test@test5.com',
                'contact' => '8888888888',
            ]
        );

        $fundAccount = $this->createFundAccount($contact->getPublicId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/contacts?fund_account_id=' . $fundAccount['id'];

        $this->startTest($data);
    }

    public function testFetchContactByActive()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id' => '1000005contact',
                'email' => 'test@test5.com',
                'contact' => '8888888888',
                'active' => 1,
            ]
        );

        $fundAccount = $this->createFundAccount($contact->getPublicId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/contacts?active=1';

        $this->startTest($data);
    }

    public function testFetchContactByType()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id' => '1000005contact',
                'email' => 'test@test5.com',
                'contact' => '8888888888',
                'active' => 1,
                'type' => 'customer',
            ]
        );

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

    public function testUpdateContactWithObserver()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self', 'reference_id' => '213']);

        Carbon::setTestNow(Carbon::now(Timezone::IST)->addMinutes(5));

        $splitzMock = \Mockery::mock(SplitzService::class);
        
        $splitzMock->shouldReceive("evaluateRequest")->andReturn([
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ]);

        $this->app->instance('splitzService', $splitzMock);

        $metroMock = \Mockery::mock('RZP\Metro\MetroHandler');

        $metroMock->shouldReceive("publish")->andReturn([]);

        $this->app->instance('metro', $metroMock);

        $this->startTest();

        $expectedMetroMessage = [
            "data" => json_encode([
                "id" => "cont_1000000contact",
                "change_set" => ["type" => "employee", "updated_at" => Carbon::now()->getTimestamp()],
            ]),
            "attributes" => ["type" => "employee"],
        ];

        $metroMock->shouldHaveReceived("publish")->withArgs(["contact-entity-update-test", $expectedMetroMessage]);

        // Test negative scenario where publishing to metro fails.

        $splitzMock = \Mockery::mock(SplitzService::class);

        $splitzMock->shouldReceive("evaluateRequest")->andReturn([
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ]);

        $this->app->instance('splitzService', $splitzMock);

        $metroMock = \Mockery::mock('RZP\Metro\MetroHandler');

        $metroMock->shouldReceive("publish")->andThrow(new \Exception("publishing failure"));

        $this->app->instance('metro', $metroMock);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['type'] = 'customer';
        $data['response']['content']['type'] = 'customer';

        $this->startTest($data);

        $expectedMetroMessage = [
            "data" => json_encode([
                "id" => "cont_1000000contact",
                "change_set" => ["type" => "customer"],
            ]),
            "attributes" => ["type" => "customer"],
        ];

        $metroMock->shouldHaveReceived("publish")->withArgs(["contact-entity-update-test", $expectedMetroMessage]);

        // Test negative scenario where contact type is null.

        $splitzMock = \Mockery::mock(SplitzService::class);

        $splitzMock->shouldReceive("evaluateRequest")->andReturn([
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ]);

        $this->app->instance('splitzService', $splitzMock);

        $metroMock = \Mockery::mock('RZP\Metro\MetroHandler');

        $metroMock->shouldReceive("publish")->andThrow(new \Exception("publishing failure"));

        $this->app->instance('metro', $metroMock);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['type'] = null;
        $data['response']['content']['type'] = null;

        $this->startTest($data);

        $expectedMetroMessage = [
            "data" => json_encode([
                "id" => "cont_1000000contact",
                "change_set" => ["type" => null],
            ]),
            "attributes" => ["type" => ""],
        ];

        $metroMock->shouldHaveReceived("publish")->withArgs(["contact-entity-update-test", $expectedMetroMessage]);
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

        $this->assertEquals($response['id'], $contact['id']);
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
                    'account_type' => 'bank_account',
                    'contact_id'   => $contactId,
                    'bank_account'      => [
                        'name'           => 'test',
                        'ifsc'           => 'SBIN0007105',
                        'account_number' => '111000',
                    ],
                ],
            ],
            'response' => [
                'content' => [
                ],
                'status_code' => 201
            ],
        ];

        return $this->runRequestResponseFlow($testdata);
    }

    public function testGetContactPublic()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact', 'name' => 'Contact X']);

        $this->startTest();
    }

    public function testCreateContactBulk()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreateContactBulkPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDeactivateContact()
    {
        $this->testCreateContact();

        $contact = $this->getLastEntity('contact');

        $this->assertEquals($contact['active'], true);

        $contactId = $contact['id'];

        $request =  [
            'content' => [
                'active' => 0
            ],
            'url'     => '/contacts/' . $contactId,
            'method'  => 'PATCH'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['active'], false);
        $this->assertEquals($response['id'], $contactId);
    }

    public function testGetContactTypes()
    {
        $this->startTest();
    }

    public function testAddCustomContactType()
    {
        $this->startTest();
    }

    public function testAddCustomContactTypeThatAlreadyExists()
    {
        $this->testAddCustomContactType();

        $this->startTest();
    }

    public function testAdd101CustomContactTypes()
    {
        for ($count = 0; $count < 100; $count++)
        {
            $request =  [
                'content' => [
                    'type' => 'Payout to Mehul '. $count
                ],
                'url'     => '/contacts/types',
                'method'  => 'POST'
            ];

            $this->makeRequestAndGetContent($request);
        }

        $this->startTest();
    }

    public function testCreateBulkContactsMoreThanAllowedNumber()
    {
        $this->ba->batchAuth();

        $content = [];

        for ($count = 0; $count < 16; $count++)
        {
            $contentData = [
                'fund'  => [
                    'account_type'      => 'bank_account',
                    'account_name'      => 'Sample rzp' . $count,
                    'account_IFSC'      => 'SBIN0007106',
                    'account_number'    => '123456789' . $count,
                    'account_vpa'       => ''
                ],
                'contact'  => [
                    'id'                => '',
                    'type'              => 'self',
                    'name'              => 'Test rzp' . $count,
                    'email'             => 'sample@example' . $count . '.com',
                    'mobile'            => '998899889' . $count,
                    'reference_id'      => ''
                ],
                'notes'  => [
                    'code'              => 'abc123',
                    'place'             => 'Bangalore',
                    'state'             => 'Karnataka'
                ],
                'idempotency_key'       => 'batch_abc' . $count
            ];

            array_push($content, $contentData);
        }

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;
        $this->testData[__FUNCTION__]['request']['content'] = $content;

        $this->startTest();
    }

    public function testCreateContactBulkWithoutBatchId()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreateBulkContactsInvalidName()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreateBulkContactsInvalidType()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testAddCustomContactTypeRZPFees()
    {
        $this->startTest();
    }

    public function testCreateRZPFeesTypeContact()
    {
        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testUpdateRZPFeesContact()
    {
        $this->testCreateContact();

        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->edit('contact', $contact->getId(), ['type' => 'rzp_fees']);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/contacts/' . $contact->getPublicId();

        $this->startTest();
    }

    public function testUpdateContactTypeToRZPFeesContact()
    {
        $this->testCreateContact();

        $contact = $this->getDbLastEntity('contact');

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/contacts/' . $contact->getPublicId();

        $this->startTest();
    }

    public function testCreateContactWithTypeNull()
    {
        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateContactWithTypeEmptyString()
    {
        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateContactWithNull()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self', 'reference_id' => '213', 'email' => 'test@gmail.com']);

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateContactWithEmailNull()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self', 'reference_id' => '213', 'email' => 'test@gmail.com']);

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateContactWithEmptyString()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self', 'reference_id' => '213']);

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateContactWithIdempotencyKey()
    {
        $this->startTest();
    }

    public function testCreateContactWithDuplicateIdempotencyKey()
    {
        $this->testCreateContactWithIdempotencyKey();

        $contact1 = $this->getLastEntity('contact', true);

        $this->ba->privateAuth();

        $this->startTest();

        $contact2 = $this->getLastEntity('contact', true);

        $this->assertEquals($contact1['id'], $contact2['id']);
    }

    public function testFetchContactsById()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'email' => 'test@test1.com']);
        $this->fixtures->create('contact', ['id' => '1000002contact', 'email' => 'random@test.com']);

        $this->startTest();
    }

    public function testCreateContactWithCustomType()
    {
        $this->testAddCustomContactType();

        $this->startTest();
    }

    // check trimming in contact create when experiment is not on for merchant.
    public function testCreateContactWithUnnecessarySpacesTrimmedInNameAndType()
    {
        $this->startTest();
    }

    // check trimming in contact create proxy auth.
    public function testCreateContactWithUnnecessarySpacesTrimmedInNameAndTypeAndProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    // check trimming in contact update.
    public function testUpdateContactWithUnnecessarySpacesTrimmedInNameAndType()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'self', 'reference_id' => '213']);

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    // check trimming in contact type creation.
    public function testAddCustomContactTypeWithSpacesTrimmed()
    {
        $type = & $this->testData[__FUNCTION__]['request']['content']['type'];

        $customType = ' leading trailing ';

        $type = $customType;

        $this->startTest();

        return $customType;
    }

    // check trimming in contact type creation when experiment is not on for merchant.
    public function testAddCustomContactTypeThatAlreadyExistsTrimmedType()
    {
        $customType = $this->testAddCustomContactTypeWithSpacesTrimmed();

        $type = & $this->testData[__FUNCTION__]['request']['content']['type'];

        $description = & $this->testData[__FUNCTION__]['response']['content']['error']['description'];

        $type = trim($customType);

        $description = sprintf($description, $type);

        $this->startTest();
    }

    public function testUpdateContactCheckType()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'employee', 'reference_id' => null]);

        $contact = $this->getDbLastEntity('contact');

        $this->assertNull($contact->getReferenceId());

        $this->startTest();

        $contact->reload();

        $this->assertNotNull($contact->getReferenceId());
        $this->assertNotNull($contact->getType());
    }

    public function testCreateContactWithoutNameNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->startTest();
    }

    public function testCreateContactWithoutNameNewApiErrorOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1,]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateRZPFeesTypeContactNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->startTest();
    }

    public function testCreateRZPFeesTypeContactNewApiErrorOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1,]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateRZPCapitalCollectionsTypeContact()
    {
        $merchant = $this->fixtures->create('merchant');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->capitalCollectionsAuth();

        $this->startTest();

        $contactDb = $this->getDbLastEntity('contact');

        $this->assertEquals($contactDb['type'],'rzp_capital_collections');
    }

    public function testCreateRZPCaptialCollectionsTypeContactByOtherInternalAppFailure()
    {
        $merchant = $this->fixtures->create('merchant');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->xPayrollAuth();

        $this->startTest();
    }

    public function testCreateRZPXpayrollTypeContact()
    {
        $merchant = $this->fixtures->create('merchant');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->xPayrollAuth();

        $this->startTest();

        $contact = $this->getDbLastEntity('contact');

        $this->assertEquals($contact['type'],'rzp_xpayroll');
    }

    public function testCreateRZPXpayrolltypeContactByOtherInternalAppFailure()
    {
        $merchant = $this->fixtures->create('merchant');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }

    public function testTrimContactName()
    {
        $contact = $this->fixtures->create('contact', ['name'=>' test contact ']);
        $contactId = $contact['id'];
        $merchantId = $contact['merchant_id'];
        $creationTime = $contact['created_at'];

        $pagination = new PaginationEntity();
        $pagination->setAttribute(PaginationEntity::WHITELIST_MERCHANT_IDS, [$merchantId]);
        $pagination->setAttribute(PaginationEntity::LIMIT, 1000);
        $pagination->setAttribute(PaginationEntity::START_TIME,$creationTime);
        $pagination->setAttribute(PaginationEntity::END_TIME, $creationTime);
        $pagination->setAttribute(PaginationEntity::DURATION, 0);
        $pagination->build();

        $core = new Core();
        $core->trimContactName($pagination);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/contacts/cont_'.$contactId;
        $testData['response']['content']['id'] = 'cont_'.$contactId;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testTrimContactType()
    {
        $merchantId = '10000000000000';
        //create a type with spaces
        $testData = $this->testData[__FUNCTION__];

        $request = [
            'url'     => '/contacts/types',
            'method'  => 'POST',
            'content' => [
                'type' => ' test type ',
            ],
        ];

        $response = [
            'content' => [
                'entity' => "collection",
            ]
        ];

        $testData['request'] = $request;
        $testData['response'] = $response;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        //create contact with new type
        $contact = $this->fixtures->create('contact',['name'=> 'test contact', 'type'=>' test type ']);
        $contactId = $contact['id'];
        $creationTime = $contact['created_at'];

        $pagination = new PaginationEntity();
        $pagination->setAttribute(PaginationEntity::WHITELIST_MERCHANT_IDS, [$merchantId]);
        $pagination->setAttribute(PaginationEntity::LIMIT, 1000);
        $pagination->setAttribute(PaginationEntity::START_TIME,$creationTime);
        $pagination->setAttribute(PaginationEntity::END_TIME, $creationTime);
        $pagination->setAttribute(PaginationEntity::DURATION, 0);
        $pagination->build();

        $core = new Core();
        $core->trimContactType($pagination);

        //get current contact and check its type
        $request = [
            'url'    => '/contacts/cont_'.$contactId,
            'method' => 'GET'
        ];

        $response = [
            'content' => [
                'id'     => 'cont_'.$contactId,
                'entity' => 'contact',
                'name' => 'test contact',
                'type' => 'test type',
            ],
        ];

        $testData['request'] = $request;
        $testData['response'] = $response;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }
}
