<?php

namespace RZP\Tests\Functional\PaymentLink;

use DB;
use Event;
use Carbon\Carbon;
use Mail;
use Mockery;

use Illuminate\Http\UploadedFile;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Feature\Constants;
use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Models\Currency\Currency;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\Schedule;
use RZP\Services\BatchMicroService;
use RZP\Services\Elfin\Impl\Gimli;
use RZP\Jobs\PaymentPageProcessor;
use Illuminate\Support\Facades\Bus;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Support\Facades\Config;
use Illuminate\Cache\Events\CacheMissed;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Services\Mock;
use RZP\Services\Elfin;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Models\PaymentLink as PaymentLinkModel;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PaymentLink\CustomDomain\Plans as CDSPlans;
use RZP\Services\RazorXClient;

class PaymentLinkTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';
    const TEST_PLAN_ID  = '1000000000plan';

    const TEST_MID      = '10000000000000';
    const TEST_NCU_ID   = '10000000000ncu';
    const TEST_NCU_ID_2 = '10000000001ncu';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testPaymentLinkMakePaymentWhenPageIsInactive()
    {
        $this->createPaymentLinkAndOrderForThat(['id' => self::TEST_PL_ID], ['id' => self::TEST_ORDER_ID]);

        $this->fixtures->edit('payment_link',self::TEST_PL_ID,['status' => 'inactive']);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPaymentLinkMakePaymentWithDifferentOrder()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->fixtures->create('order', [
            'id'     => self::TEST_ORDER_ID,
            'amount' => 10000,
        ]);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPaymentLinkMakePaymentWithoutOrder()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWhenPageIsInactive()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                    PaymentLink\PaymentPageItem\Entity::STOCK => 5,
                    PaymentLink\PaymentPageItem\Entity::QUANTITY_SOLD => 5,
                ],
            ],
            PaymentLink\Entity::STATUS => PaymentLink\Status::INACTIVE,
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWhenQuantitySoldOut()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                    PaymentLink\PaymentPageItem\Entity::STOCK => 5,
                    PaymentLink\PaymentPageItem\Entity::QUANTITY_SOLD => 5,
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => false,
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithDuplicateItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MIN_PURCHASE => 3
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithRequiredItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => false,
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithoutRequiredItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => false,
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithPurchaseLesserThanMinPurchase()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MIN_PURCHASE => 3
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithPurchaseGreaterThanMaxPurchase()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MAX_PURCHASE => 3
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithFixedAmount()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithAmountGreaterThanMaxAmount()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MAX_AMOUNT => 500
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithAmountLessThanMinAmount()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MIN_AMOUNT => 5000
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testUpdatePaymentLinkRemoveAllItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testUpdatePaymentLinkAddingItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ]);

        $this->startTest();
    }


    public function testUpdatePaymentLinkFileUpload()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $request = $this->testData['testPaymentPageCreateForFileUpload'];

        $response = $this->runRequestResponseFlow($request);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payment_pages/'.$response['id'].'/';

        $this->startTest($testData);
    }

    public function testUpdatePaymentLinkFileUploadException()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $request = $this->testData['testPaymentPageCreateForFileUpload'];

        $response = $this->runRequestResponseFlow($request);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payment_pages/'.$response['id'].'/';

        $this->startTest($testData);
    }

    public function testUpdatePaymentLinkFileUploadWithoutSecondaryReferenceId1()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $request = $this->testData['testPaymentPageCreateForFileUpload'];

        $response = $this->runRequestResponseFlow($request);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payment_pages/'.$response['id'].'/';

        $this->startTest($testData);
    }

    public function testUpdatePaymentLinkFileUploadWithoutFeature()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $request = $this->testData['testPaymentPageCreateForFileUpload'];

        $response = $this->runRequestResponseFlow($request);

        $this->fixtures->merchant->removeFeatures([Constants::FILE_UPLOAD_PP]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payment_pages/'.$response['id'].'/';

        $this->startTest($testData);
    }

    public function testUpdatePaymentLinkDeletingItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ]);

        $response = $this->startTest();

        $this->assertEquals(1, sizeof($response[PaymentLink\Entity::PAYMENT_PAGE_ITEMS] ?? []));
    }

    public function testCreatePaymentLinkWithoutItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMoreThanLimitedItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithDifferentCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->startTest();
    }

    public function testCreatePaymentLinkByPassingAmountWhenAmountPassed()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMinPurchaseGreaterThanMaxPurchase()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMinAmountGreaterThanMaxAmount()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithSinglePaymentPageItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMultiplePaymentPageItem()
    {
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'page');
    }

    public function testCreatePaymentButtonWithMultipleItems()
    {
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'button');
    }

    public function testCreatePaymentLinkWithoutAmountOrCurrency()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithBadExpireBy()
    {
        $this->startTest();
    }

    public function testPaymentPageCreateWithNonUtf8InTerms()
    {
        $this->startTest();
    }

    public function testPaymentPageCreateForFileUploadAllFieldsInSettings()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $res = $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');

        $settings = $this->getDbLastEntity("settings");

        self::assertEquals($settings['key'], 'all_fields');

        self::assertEquals($settings['value'], "{\"Email\":\"field_1\",\"Phone\":\"field_2\",\"contact\":\"field_3\",\"Address\":\"field_4\",\"DOB\":\"field_5\",\"item1\":\"field_6\",\"item2\":\"field_7\"}");

        return $res['id'];
    }

    public function testPaymentPageUpdateForFileUploadAllFieldsInSettings()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id;

        $this->startTest();

        $settings = $this->getDbLastEntity("settings");

        self::assertEquals($settings['key'], 'all_fields');

        self::assertEquals($settings['value'], "{\"Email\":\"field_1\",\"Phone\":\"field_2\",\"contact\":\"field_3\",\"Address\":\"field_4\",\"DOB\":\"field_5\",\"item1\":\"field_6\",\"item2\":\"field_7\",\"Father Name\":\"field_8\",\"item3\":\"field_9\"}");
    }

    public function testCreatePaymentPageRecordWithCustomFieldsSchema()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $resp = $this->startTest();

        $entity = $this->getDbLastEntity("payment_page_record");

        $entityArray = $entity->toArray();

        $this->assertEquals($entityArray['custom_field_schema'], '{"field_4": {"key": "Address", "value": "test", "dataType": "string"}, "field_5": {"key": "DOB", "value": "test123", "dataType": "string"}, "field_6": {"key": "item1", "value": "100001", "dataType": "string"}, "field_7": {"key": "item2", "value": "20000", "dataType": "string"}}');
    }

    public function testFetchPaymentPageRecordsAfterPPUpdate()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $testData = $this->testData['testCreatePaymentPageRecordWithCustomFieldsSchema'];

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $testData['request']['content']['Phone'] = '1234567890';

        $testData['request']['content']['DOB'] = 'test123';

        $this->ba->batchAppAuth();

        $content = $this->makeRequestAndGetContent($testData["request"]);

        // update the payment page

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id;

        $this->ba->proxyAuth();

        $this->startTest();

        // fetch payment page record

        $testData = $this->testData['testFetchRecordsForPL'];

        $testData['request']['url'] = '/payment_pages/'. $id . '/fetch_records';

        $this->ba->directAuth();

        $testData['request']['content']['pri__ref__id'] = '1234567890';

        $testData['request']['content']['sec__ref__id_1'] = 'test123';

        $content = $this->makeRequestAndGetContent($testData["request"]);
    }

    public function testPaymentPageCreateWithMoreThan5SeccRefIds()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $this->startTest();
    }

    public function setupPaymentPageForUDFSchemaValidations()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $resp = $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');

        return $resp['id'];
    }

    public function testPaymentPageRecordRegexValidationPositive()
    {
        $id = $this->setupPaymentPageForUDFSchemaValidations();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $testData['request']['content'] = [
            'amount'         => '101',
            'sms_notify'     => TRUE,
            'email_notify'   => TRUE,
            'Email' => 'test@test.com',
            'Phone' => 1231231234,
            'Primary Reference Id' => 'test123123',
            'Secondary Reference Id' => 'test123',
            'URL' => 'https://razorpay.com',
            'PAN' => 'ABCDP1234X',
            'DOB' => '16 Jun, 2023',
            'Amount 2' => 1234.56
        ];

        $this->ba->batchAppAuth();

        $resp = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($resp["error_code"], "");
        $this->assertEquals($resp["error_description"], "");
    }

    // invalid email
    public function testPaymentPageRecordRegexValidationNegative1()
    {
        $id = $this->setupPaymentPageForUDFSchemaValidations();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $testData['request']['content'] = [
            'amount'         => '101',
            'sms_notify'     => TRUE,
            'email_notify'   => TRUE,
            'Email' => 'testtest.com',
            'Phone' => 1231231234,
            'Primary Reference Id' => 'test123123',
            'Secondary Reference Id' => 'test123',
            'URL' => 'https://razorpay.com',
            'PAN' => 'ABCDP1234X',
            'DOB' => '16 Jun, 2023',
            'Amount 2' => 1234.56
        ];

        $this->ba->batchAppAuth();

        $resp = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($resp["error_code"], "BAD_REQUEST_VALIDATION_FAILURE");
        $this->assertEquals($resp["error_description"], "The email field is invalid. Does not match the regex pattern ^(?i)(([^<>()\\[\\]\\.,;:\\s@\"]+(\\.[^<>()\\[\\]\\.,;:\\s@\"]+)*)|(\".+\"))@((\\[[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\])|(([a-zA-Z\\-0-9]+\\.)+[a-zA-Z]{2,}))$");
    }


    // invalid phone
    public function testPaymentPageRecordRegexValidationNegative2()
    {
        $id = $this->setupPaymentPageForUDFSchemaValidations();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $testData['request']['content'] = [
            'amount'         => '101',
            'sms_notify'     => TRUE,
            'email_notify'   => TRUE,
            'Email' => 'test@test.com',
            'Phone' => 123456,
            'URL' => 'https://razorpay.com',
            'Primary Reference Id' => 'test123123',
            'Secondary Reference Id' => 'test123',
            'PAN' => 'ABCDP1234X',
            'DOB' => '16 Jun, 2023',
            'Amount 2' => 1234.56
        ];

        $this->ba->batchAppAuth();

        $resp = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($resp["error_code"], "BAD_REQUEST_VALIDATION_FAILURE");
        $this->assertEquals($resp["error_description"], "The phone field is invalid. Does not match the regex pattern ^([0-9]){8,}$");
    }


    // invalid dob
    public function testPaymentPageRecordRegexValidationNegative3()
    {
        $id = $this->setupPaymentPageForUDFSchemaValidations();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $testData['request']['content'] = [
            'amount'         => '101',
            'sms_notify'     => TRUE,
            'email_notify'   => TRUE,
            'Email' => 'test@test.com',
            'Phone' => 12345678,
            'URL' => 'https://razorpay.com',
            'Primary Reference Id' => 'test123123',
            'Secondary Reference Id' => 'test123',
            'PAN' => 'ABCDP1234X',
            'DOB' => '1612',
            'Amount 2' => 1234.56
        ];

        $this->ba->batchAppAuth();

        $resp = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($resp["error_code"], "BAD_REQUEST_VALIDATION_FAILURE");
        $this->assertEquals($resp["error_description"], "The dob field is invalid. Does not match the regex pattern ^(([0]?[1-9])?|([1-2][0-9])?|([3][0,1])?) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)(, | )(1[6-8][0-9]{2}|19[0-8][0-9]|199[0-9]|[2-9][0-9]{3})$");
    }

    // invalid pan
    public function testPaymentPageRecordRegexValidationNegative4()
    {
        $id = $this->setupPaymentPageForUDFSchemaValidations();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $testData['request']['content'] = [
            'amount'         => '101',
            'sms_notify'     => TRUE,
            'email_notify'   => TRUE,
            'Email' => 'test@test.com',
            'Phone' => 12345678,
            'URL' => 'https://razorpay.com',
            'Primary Reference Id' => 'test123123',
            'Secondary Reference Id' => 'test123',
            'PAN' => '123',
            'DOB' => '16 Jun, 2023',
            'Amount 2' => 1234.56
        ];

        $this->ba->batchAppAuth();

        $resp = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($resp["error_code"], "BAD_REQUEST_VALIDATION_FAILURE");
        $this->assertEquals($resp["error_description"], "The pan field is invalid. Does not match the regex pattern ^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$");
    }

    public function testCreatePaymentPageRecordSecurityValidations()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $resp = $this->startTest();

        $entity = $this->getDbLastEntity("payment_page_record");

        $entityArray = $entity->toArray();
    }

    public function testCreatePaymentPageRecordSecurityValidationsNegative()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $resp = $this->startTest();
    }

    public function testCreatePaymentPageRecordSecurityValidationsNegative2()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $content = $this->makeRequestAndGetContent($testData["request"]);

        $testData['request']['content']['Phone'] = 'newprimaryRef';
        $content = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($content["error_description"], "Secondary reference id should be unique, duplicate value for test123");
    }

    public function testCreatePaymentPageRecordSecurityValidationsNegative3()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $content = $this->makeRequestAndGetContent($testData["request"]);

        $content = $this->makeRequestAndGetContent($testData["request"]);

        $this->assertEquals($content["error_description"], 'Primary Reference ID should be unique');
    }

    public function testPaymentPageCreateForFileUpload()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');
    }

    public function testPaymentPageCreateForFileUploadWithoutSecondaryReferenceId1()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();
    }

    public function testPaymentPageCreateForFileUploadWithPrimaryRefIdNotRequired()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();
    }

    public function testPaymentPageCreateForFileUploadWithSecRefId1NotRequired()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();
    }

    public function testPaymentPageCreateWithSameTitleForPrimaryAndSecRefIds()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();
    }

    public function testPaymentPageCreateForFileUploadWithoutPhone()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');
    }

    public function testPaymentPageCreateForFileUploadWithSecRefId()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');
    }

    public function testPaymentPageCreateForFileUploadWithoutFeature()
    {
        $this->startTest();
    }

    public function testPaymentPageCreateForFileUploadMissingPrimaryRefID()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $this->startTest();
    }

    public function setUpPaymentPageForFileUpload()
    {

        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $resp = $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');

        return $resp['id'];
    }

    protected function createPaymentPageRecords($id = null)
    {
        $id = $id ?? $this->setUpPaymentPageForFileUpload();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testPaymentPageRecordForFileUpload'];

        $testData['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $this->sendRequest($testData['request']);

        $entity = $this->getDbLastEntity('payment_page_record');

        $entityArray = $entity->toArray();

        return $entityArray['payment_link_id'];
    }

    public function testPaymentPageStatusUpdate()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $testData = $this->testData['setUpPaymentPageForFileUpload'];

        $testData['request']['content']['payment_page_items'] = [
            [
                PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                    'name'        =>  'amount',
                    Item\Entity::AMOUNT => 5000,
                    'currency'    => 'INR',
                ],
                'mandatory'         => true,
            ],
            [
                PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                    'name'        =>  'testName2',
                    Item\Entity::AMOUNT => 10000,
                    'currency'    => 'INR',
                ],
                'mandatory'         => false,
            ]
        ];

        $resp = $this->startTest($testData);

        $paymentLink = (new PaymentLink\Repository())->findByPublicId($resp['id']);

        $paymentPageItems = (new PaymentLink\PaymentPageItem\Repository())->fetchByPaymentLinkIdAndMerchant($paymentLink->getId(), '10000000000000');

        $this->createPaymentPageRecords($paymentLink->getId());

        $this->createOrderForPaymentLink($paymentPageItems);

        $orderEntity = $this->getDbLastEntity("order");

        $order = $orderEntity;

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order, Payment\Status::CAPTURED,[
            'pri__ref__id'  => '1234567890',
            'sec__ref__id_1' => '123456789',
            'email' => 'abc@abc.com',
            'phone' => '1234567890',
        ]);

        $entity = $this->getDbLastEntity('payment_page_record');

        $entityArray = $entity->toArray();

        $this->assertEquals('paid',$entityArray['status']);

    }


    public function testPaymentPageStatusUpdateWithoutFeatureFlag()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $testData = $this->testData['setUpPaymentPageForFileUpload'];

        $testData['request']['content']['payment_page_items'] = [
            [
                PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                    'name'        =>  'amount',
                    Item\Entity::AMOUNT => 5000,
                    'currency'    => 'INR',
                ],
                'mandatory'         => true,
            ],
            [
                PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                    'name'        =>  'testName2',
                    Item\Entity::AMOUNT => 10000,
                    'currency'    => 'INR',
                ],
                'mandatory'         => false,
            ]
        ];

        $resp = $this->startTest($testData);

        $paymentLink = (new PaymentLink\Repository())->findByPublicId($resp['id']);

        $paymentPageItems = (new PaymentLink\PaymentPageItem\Repository())->fetchByPaymentLinkIdAndMerchant($paymentLink->getId(), '10000000000000');

        $this->createPaymentPageRecords($paymentLink->getId());

        $this->createOrderForPaymentLink($paymentPageItems);

        $orderEntity = $this->getDbLastEntity("order");

        $order = $orderEntity;

        // remove feature to ensure payment page record is still getting updated
        $this->fixtures->merchant->removeFeatures([Constants::FILE_UPLOAD_PP]);

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order, Payment\Status::CAPTURED,[
            'pri__ref__id'  => '1234567890',
            'sec__ref__id_1' => '123456789',
            'email' => 'abc@abc.com',
            'phone' => '1234567890',
        ]);

        $entity = $this->getDbLastEntity('payment_page_record');

        $entityArray = $entity->toArray();

        $this->assertEquals('paid',$entityArray['status']);

    }

    public function testPaymentPageRecordForFileUpload()
    {
        $id = $this->setUpPaymentPageForFileUpload();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $resp = $this->startTest();

        $entity = $this->getDbLastEntity('payment_page_record');

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['primary_reference_id'], '1234567890');
        self::assertEquals($entityArray['email'], 'paridhi.jain@rzp.com');
        self::assertEquals($entityArray['contact'], '0987654321');
    }

    public function testPaymentPageRecordForFileUploadAmountValidationFailure()
    {
        $id = $this->setUpPaymentPageForFileUpload();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $this->startTest();

    }

    public function testPaymentPageRecordForFileUploadMissingUdfParams()
    {
        $id = $this->setUpPaymentPageForFileUpload();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $this->startTest();

    }

    public function testPaymentPageRecordForFileUploadAmountLessTHanMinAllowed()
    {
        $id = $this->setUpPaymentPageForFileUpload();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $this->startTest();

    }

    public function setUpCreateRecordForFileUpload($setupFunctionName="setUpPaymentPageForFileUpload")
    {
        $id = $this->$setupFunctionName();

        $batch_id = 'batch_KoGILWQCoVkOz5';

        $testData = $this->testData['testPaymentPageRecordForFileUpload'];

        $testData['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $this->sendRequest($testData['request']);

        $testData['request']['content']['Phone'] = '883344';

        $testData['request']['content']['amount'] = '200';

        $testData['request']['content']['DOB'] = 'test123';

        $batch_id = 'batch_KoGILWQCoVkOz6';

        $testData['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->sendRequest($testData['request']);

        return $id;
    }

    public function setUpCreateRecordForFileUploadGetBatch()
    {
        $id = $this->setUpPaymentPageForFileUpload();

        $batch_id = 'batch_00000000000001';

        $testData = $this->testData['testPaymentPageRecordForFileUpload'];

        $testData['request']['url'] = '/payment_pages/'. $id . '/create_record/'. $batch_id;

        $this->ba->batchAppAuth();

        $resp = $this->sendRequest($testData['request']);

        return $id;
    }

    public function testPaymentPagePendingPaymentsAndRevenue()
    {
        $id = $this->setUpCreateRecordForFileUpload();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/pending_payments/';

        $this->ba->proxyAuth();

        $resp = $this->startTest();

        self::assertEquals($resp['total_pending_payments'], 2);
        self::assertEquals($resp['total_pending_revenue'], 301);
    }

    protected function mockBatchService()
    {
        $mock = Mockery::mock(BatchMicroService::class)->makePartial();
        $this->app->instance('batchService', $mock);

        $batchEntity = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000001',
                'type'        => 'payment_page',
                'status'      => 'COMPLETED'
            ]);
        $mock->shouldAllowMockingMethod('getMultipleBatchesFromBatchService')
            ->shouldReceive('getMultipleBatchesFromBatchService')
            ->andReturn([$batchEntity]);
    }

    public function testGetMultipleBatchesForPaymentPage()
    {
        $this->mockBatchService();

        $id = $this->setUpCreateRecordForFileUploadGetBatch();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/batches?count=20&skip=0';
        $this->testData[__FUNCTION__]['request']['method'] = 'GET';

        $this->ba->proxyAuth();
        $this->startTest();

    }

    public function testGetMultipleBatchesForPaymentPageCount()
    {
        $this->mockBatchService();

        $id = $this->setUpCreateRecordForFileUploadGetBatch();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/batches?count=26&skip=0';
        $this->testData[__FUNCTION__]['request']['method'] = 'GET';

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testGetAllBatchesForPaymentPage()
    {
        $id = $this->testPaymentPageCreateForFileUploadAllFieldsInSettings();

        $testData = $this->testData['testCreatePaymentPageRecordSecurityValidations'];

        $batch_id1 = 'batch_KoGILWQCoVkOz5';
        $batch_id2 = 'batch_KoGILWQCoVkOw7';
        $batch_id3 = 'batch_KoGILWQCoVkO0s';
        $batch_id4 = 'batch_KoGILWQCoVkO2k';

        $this->ba->batchAppAuth();

        $testData['request']['content']['Phone'] = 'pr1';
        $testData['request']['content']['DOB'] = 'sr1';
        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id1;
        $this->makeRequestAndGetContent($testData["request"]);

        $testData['request']['content']['Phone'] = 'pr2';
        $testData['request']['content']['DOB'] = 'sr2';
        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id2;
        $this->makeRequestAndGetContent($testData["request"]);


        $testData['request']['content']['Phone'] = 'pr3';
        $testData['request']['content']['DOB'] = 'sr3';
        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id3;
        $this->makeRequestAndGetContent($testData["request"]);


        $testData['request']['content']['Phone'] = 'pr4';
        $testData['request']['content']['DOB'] = 'sr4';
        $testData['request']['url'] = '/payment_pages/'. $id. '/create_record/'. $batch_id4;
        $this->makeRequestAndGetContent($testData["request"]);


        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $id . '/batches?count=20&skip=0';
        $this->testData[__FUNCTION__]['request']['method'] = 'GET';

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function setUpPaymentPageWithSecRefIdForFileUpload()
    {

        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $testdata = $this->testData['testPaymentPageCreateForFileUploadWithSecRefId'];

        $resp = $this->startTest($testdata);

        $entity = $this->getDbLastEntity("payment_link");

        return $resp['id'];

    }

    public function testFetchRecordsForPL()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->setUpCreateRecordForFileUpload("setUpPaymentPageWithSecRefIdForFileUpload");

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $res . '/fetch_records';

        $this->ba->directAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testFetchRecordsForPLIdFailure()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = 'pl_1000000000001l';

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $res . '/fetch_records';

        $this->ba->directAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testFetchRecordsForPLWithOnlyPrimaryRefId()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->setUpCreateRecordForFileUpload();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $res . '/fetch_records';

        $this->ba->directAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testFetchRecordsForPLWithOnlyPrimaryRefIdFailure()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->setUpCreateRecordForFileUpload("setUpPaymentPageWithSecRefIdForFileUpload");

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $res . '/fetch_records';

        $this->ba->directAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testFetchRecordsForPLFailure()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->setUpCreateRecordForFileUpload("setUpPaymentPageWithSecRefIdForFileUpload");

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $res . '/fetch_records';

        $this->ba->directAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testFetchRecordsForPLFailureIncorrectPriRefId()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->setUpCreateRecordForFileUpload("setUpPaymentPageWithSecRefIdForFileUpload");

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/'. $res . '/fetch_records';

        $this->ba->directAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testFetchPaymentLink()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testFetchPaymentLinks()
    {
        $this->testCreatePaymentLinkWithMultiplePaymentPageItem();
        $this->startTest();
    }

    public function testFetchPaymentLinksForFileUpload()
    {
        $this->testCreatePaymentLinkWithMultiplePaymentPageItem();
        $this->testCreatePaymentLinkWithMultiplePaymentPageItem();
        $this->testPaymentPageCreateForFileUpload();
        $this->startTest();
    }

    public function testFetchPaymentLinksForFileUploadWithoutFeature()
    {
        $this->startTest();
    }


    public function testFetchPaymentButtons()
    {
        $this->testCreatePaymentButtonWithMultipleItems();
        $this->startTest();
    }

    public function testFetchButtonNotInPagesList()
    {
        $this->testCreatePaymentLinkWithMultiplePaymentPageItem();
        $this->startTest();
    }

    public function testFetchPageNotInButtonList()
    {
        $this->testCreatePaymentButtonWithMultipleItems();
        $this->startTest();
    }

    public function testFetchButtonPreferences()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_buttons/pl_{$id}/button_preferences");

        $response->assertStatus(200);

        $content = json_decode($response->getContent(), true);

        $response->assertHeader('Access-Control-Allow-Origin', '*');

        $this->assertArrayKeysExist($content, ['preferences', 'is_test_mode']);

        $this->assertEquals($content['preferences']['payment_button_text'], 'Please pay');

        $this->assertEquals($content['preferences']['payment_button_theme'], 'rzp-dark-standard');

        $this->assertArrayHasKey("merchant_brand_color", $content['preferences']);
    }

    public function testFetchGetButtonHostedView()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_buttons/pl_{$id}/view");

        $response->assertStatus(200);

        $this->assertStringContainsString($id, $response->getContent());
    }

    public function testOptionalFeatureInHostedPage()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('contact_optional', $response->getContent());
    }

    public function testFetchPostButtonHostedView()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('POST', "/v1/payment_buttons/pl_{$id}/view");

        $response->assertStatus(200);

        $this->assertStringContainsString($id, $response->getContent());

        $this->assertStringContainsString("udf_schema", $response->getContent());

        $this->assertStringContainsString("payment_button_text", $response->getContent());
    }

    public function testFetchPublicButtonDetails()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_buttons/pl_{$id}/button_details");

        $response->assertStatus(200);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayKeysExist($content, ['data', 'udf_schema']);

        $this->assertArrayKeysExist($content['data'], ['base_url', 'payment_link', 'merchant', 'key_id', 'is_test_mode', 'environment', 'org', 'view_preferences', 'keyless_header', 'checkout_2_enabled', 'is_pp_batch_upload']);

        $this->assertEquals($content['data']['payment_link']['id'], $entity->getPublicId());
    }

    public function testUpdatePaymentLinkWithBadExpireBy()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testPaymentLinkSendNotification()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }


    public function testPaymentLinkSendNotificationForAllRecordsEmail()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->createPaymentPageRecords();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/pl_'. $res . '/fetch_notify_details';

        $this->ba->proxyAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testPaymentLinkSendNotificationForAllRecords()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->createPaymentPageRecords();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/pl_'. $res . '/fetch_notify_details';

        $this->ba->proxyAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testPaymentLinkSendNotificationForAllRecordsSms()
    {
        $this->createPaymentLinkWithMultipleItem();

        $res = $this->createPaymentPageRecords();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/pl_'. $res . '/fetch_notify_details';

        $this->ba->proxyAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testPaymentLinkSendNotificationForAllRecordsFailure()
    {
        $res = $this->createPaymentLinkWithMultipleItem();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/pl_'. $res['id'] . '/fetch_notify_details';

        $this->ba->proxyAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testPaymentLinkSendNotificationForAllRecordsValidationFailure()
    {
        $res = $this->createPaymentLinkWithMultipleItem();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment_pages/pl_'. $res['id'] . '/fetch_notify_details';

        $this->ba->proxyAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testInactivePaymentLinkSendNotification()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::EXPIRED,
            PaymentLinkModel\Entity::EXPIRE_BY     => 1400000000,
        ];

        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testExpirePaymentLinks()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, ['expire_by' => '1400000000']);
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID_2, ['expire_by' => '1400000000']);

        $this->fixtures->create('payment_link');

        $this->ba->cronAuth();

        $this->startTest();

        $expiredPlCount = $this->getDbEntities('payment_link')
                               ->where(PaymentLinkModel\Entity::STATUS, PaymentLinkModel\Status::INACTIVE)
                               ->where(PaymentLinkModel\Entity::STATUS_REASON, PaymentLinkModel\StatusReason::EXPIRED)
                               ->count();

        $this->assertEquals(2, $expiredPlCount);
    }

    public function testPaymentLinkMakePaymentWithOrder()
    {
        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);

        $paymentPageItem1 = $this->getDbEntityById('payment_page_item', self::TEST_PPI_ID);

        $this->assertEquals(1, $paymentPageItem1->getQuantitySold());

        $this->assertEquals(5000, $paymentPageItem1->getTotalAmountPaid());

        $paymentPageItem2 = $this->getDbEntityById('payment_page_item', self::TEST_PPI_ID_2);

        $this->assertEquals(1, $paymentPageItem2->getQuantitySold());

        $this->assertEquals(10000, $paymentPageItem2->getTotalAmountPaid());
    }

    public function testPaymentLinkMakePaymentCustomerFeeBearer()
    {
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        // Enable customer fee_bearer model
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::AMOUNT] = $order->getAmount();

        $fees = $this->createAndGetFeesForPayment($payment);
        $fee  = $fees['input']['fee'];

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount() + $fee;
        $payment[Payment\Entity::FEE]             = $fee;
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($order->getAmount() + $fee, $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        // total_amount_paid must be equal to amount and not amount+fee
        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link']);
    }

    public function testPaymentLinkMakePaymentWithUdfInvalid()
    {
        $attributes = [
            PaymentLinkModel\Entity::UDF_JSONSCHEMA_ID => '10000pludftest',
        ];

        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The customer_id field is invalid. The property customer_id is required');

        $this->makePaymentForPaymentLinkAndAssert($data['payment_link'], $data['payment_link_order']['order']);
    }

    public function testPaymentLinkMakePaymentWithUdf()
    {
        $attributes = [
            PaymentLinkModel\Entity::UDF_JSONSCHEMA_ID => '10000pludftest',
        ];

        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $data['payment_link']->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $data['payment_link_order']['order']->getAmount();
        $payment[Payment\Entity::ORDER_ID]        = $data['payment_link_order']['order']->getPublicId();
        $payment[Payment\Entity::NOTES]           = [
            'customer_id'   => '1000001',
            'customer_name' => 'Random Name',
        ];

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $data['payment_link_order']['order']->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($data['payment_link_order']['order']->getAmount(), $payment->getAmount());
        $this->assertEquals($data['payment_link']->getId(), $payment->getPaymentLinkId());
    }

    public function testDeactivatePaymentLink()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testDeactivateAlreadyDeactivatedPaymentLink()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::DEACTIVATED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testActivatePaymentLink()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::DEACTIVATED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testActivateLinkAlreadyActivated()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::ACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => null,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLink()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();

    }

    public function testCreateOrderForPaymentLinkAndVerifyProductTypePage()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();

        $order = $this->getDbLastEntity("order");

        $this->assertEquals($order->getProductType(), 'payment_page');

        $this->assertEquals($order->getProductId(), self::TEST_PL_ID);
    }

    public function testCreateOrderForPaymentLinkAndVerifyProductTypeButton()
    {
        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'button']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();

        $order = $this->getDbLastEntity("order");

        $this->assertEquals($order->getProductType(), 'payment_button');

        $this->assertEquals($order->getProductId(), self::TEST_PL_ID);
    }

    public function testCreateOrderForPaymentLinkWithMultipleItem()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItems(self::TEST_PL_ID, [['id' => self::TEST_PPI_ID], ['id' => self::TEST_PPI_ID_2]]);

        $this->startTest();
    }

    public function testMinExpiryTimeForActivation()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::EXPIRED,
        ];

        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, $attributes);

        $expireBy = Carbon::now(Timezone::IST)->addSeconds(120)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['expire_by'] = $expireBy;

        $this->startTest();
    }

    public function testGetPaymentLinkView()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->callViewUrlAndMakeAssertions();
    }

    public function testGetSlugExistsApi()
    {
        $gimli = $this->getMockBuilder(Elfin\Impl\Gimli::class)
                      ->setConstructorArgs([$this->app['config']->get('applications.elfin.gimli')])
                      ->setMethods(['expand'])
                      ->getMock();

        $gimli->expects($this->once())
              ->method('expand')
              ->willReturn(null);

        $elfin = $this->getMockBuilder(Elfin\Mock\Service::class)
                      ->setConstructorArgs([$this->app['config'], $this->app['trace']])
                      ->setMethods(['driver'])
                      ->getMock();

        $elfin->expects($this->once())
              ->method('driver')
              ->with('gimli')
              ->willReturn($gimli);

        $this->app->instance('elfin', $elfin);

        $this->startTest();
    }

    public function testPaymentLinkPaymentRefundAfterNoStock()
    {
        $data = $this->createPaymentLinkAndOrderForThat(
            ['id' => self::TEST_PL_ID,
                PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                    [
                        PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                        PaymentLink\PaymentPageItem\Entity::ITEM => [
                            Item\Entity::AMOUNT => 5000,
                        ],
                        PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                        PaymentLink\PaymentPageItem\Entity::STOCK => 5,
                        PaymentLink\PaymentPageItem\Entity::QUANTITY_SOLD => 0,
                    ],
                ]
            ],
            [
                'id' => self::TEST_ORDER_ID,
                Order\Entity::PAYMENT_CAPTURE => false,
            ]
        );

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order, Payment\Status::AUTHORIZED);

        $this->fixtures->edit(
            'payment_page_item',
            self::TEST_PPI_ID,
            [
                'quantity_sold' => 5,
            ]
        );

        $this->fixtures->edit(
            'payment_link',
            self::TEST_PL_ID,
            [
                'status' => PaymentLink\Status::INACTIVE,
                'status_reason' => PaymentLink\StatusReason::COMPLETED,
            ]
        );

        $this->fixtures->edit(
            'payment',
            $payment['id'],
            [
                'auto_captured' => true,
            ]
        );

        $this->capturePayment($payment['id'], $payment['amount'], 'INR', 0, Payment\Status::REFUNDED);
    }

    public function testSetMerchantDetails()
    {
        $this->startTest();
    }

    public function testFetchMerchantDetails()
    {
        $settings = [
            PaymentLink\Entity::TEXT_80G_12A    => 'text',
            PaymentLink\Entity::IMAGE_URL_80G   => 'https://url',
        ];
        $merchant = $this->getDbEntityById('merchant', 10000000000000);
        Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->upsert($settings)
            ->save();
        $this->startTest();
    }

    public function testSetReceiptDetails()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]'
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    public function testSetReceiptDetailsEmpty()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => true,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::CUSTOM_SERIAL_NUMBER    => true,
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    public function testCreateOrderLineItemsEmptyArray()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();

    }

    public function testCreateOrderLineItemsNotArray()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testMakePaymentReceiptEnabledCustomSerialNotEnabled()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => true,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::PAYMENT_SUCCESS_MESSAGE => 'success',
        ];

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $paymentNotes = [
            'email' => 'abc@abc.com',
            'phone' =>  '1234567890'
        ];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $paymentLink,
            $order,
            Payment\Status::CAPTURED ,
            $paymentNotes);

        $invoice = $order->invoice;

        $this->assertNotNull($invoice);

        $this->assertEquals($invoice->getStatus(), Invoice\Status::PAID);

        $this->assertEquals($invoice->getAttribute(Invoice\Entity::COMMENT), 'success');

        $this->assertEquals($invoice->getReceipt(), $payment['id']);

        $this->assertLineItems($invoice->lineItems->toArray(), $order->lineItems->toArray());

    }

    public function testMakePaymentReceiptEnabledCustomSerialEnabled()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => true,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::PAYMENT_SUCCESS_MESSAGE => 'success',
            PaymentLink\Entity::CUSTOM_SERIAL_NUMBER    => true,
        ];

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $paymentNotes = [
            'email' => 'abc@abc.com',
            'phone' =>  '1234567890'
        ];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $paymentLink,
            $order,
            Payment\Status::CAPTURED ,
            $paymentNotes);

        $invoice = $order->invoice;

        $this->assertNotNull($invoice);

        $this->assertEquals($invoice->getStatus(), Invoice\Status::PAID);

        $this->assertEquals($invoice->getAttribute(Invoice\Entity::COMMENT), 'success');

        $this->assertNull($invoice->getEmailStatus());

        $this->assertNull($invoice->getReceipt());

        $this->assertLineItems($invoice->lineItems->toArray(), $order->lineItems->toArray());

    }

    public function testMakePaymentReceiptDisabled()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => false,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::PAYMENT_SUCCESS_MESSAGE => 'success'
        ];

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $paymentNotes = [
            'email' => 'abc@abc.com',
            'phone' =>  '1234567890'
        ];

        $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $paymentLink,
            $order,
            Payment\Status::CAPTURED ,
            $paymentNotes);

        $invoice = $order->invoice;

        $this->assertNull($invoice);
    }

    public function testCreateOrderForPaymentLinkAndFetchProductType()
    {
        $this->testCreateOrderForPaymentLink();

        $order = $this->getDbLastEntity('order');

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v1/orders/order_'.$order->getId().'/product_details';

        $response = $this->startTest();
    }

    public function testFetchButtonPreferencesForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'button']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();
    }

    public function testFetchButtonDetailsForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'button']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();
    }

    public function testDeactivatedPaymentPageHostedView()
    {
        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $support = $support->toArrayPublic();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'page']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->fixtures->edit('payment_link', self::TEST_PL_ID, [ 'status' => 'inactive' , 'status_reason' => 'deactivated']);

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $this->assertStringContainsString($support['email'], $response->getContent());

        $this->assertStringContainsString($support['phone'], $response->getContent());

        $this->assertStringContainsString('This page has been deactivated', $response->getContent());
    }

    public function testPaymentPageHostedViewForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $support = $support->toArrayPublic();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'page']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $this->assertStringContainsString($support['email'], $response->getContent());

        $this->assertStringContainsString($support['phone'], $response->getContent());

        $this->assertStringContainsString('This account is suspended', $response->getContent());
    }

    public function testPaymentPageHostedViewForSuspendedMerchantForCustomBrandingOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => $org->getId()]);

        $this->fixtures->edit('org', $org->getId(), ['checkout_logo_url' => 'https://www.google.com']);

        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $support = $support->toArrayPublic();

        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $this->assertStringContainsString('https:\/\/cdn.razorpay.com\/logo.svg', $response->getContent());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'entity_type' => 'org',
            'name' => 'org_custom_branding',
        ]);

        Entity::clearHostedCacheForPageId("pl_" . self::TEST_PL_ID);

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $this->assertStringContainsString('https:\/\/www.google.com', $response->getContent());
    }

    public function testFetchPaymentPageInvoiceReceiptDetails()
    {
        $this->testMakePaymentReceiptEnabledCustomSerialNotEnabled();

        $payment = $this->getDbLastEntity('payment');

        $invoice = $this->getDbLastEntity('invoice');

        $invoice->updated_at = Carbon::now()->subMinutes(2)->getTimestamp();
        $invoice->save();

        $this->ba->proxyAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/v1/payment_pages/'.$payment->getPublicId().'/receipt',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['invoice_id'], $invoice->getPublicId());

        $this->assertEquals($content['receipt'], $payment->getPublicId());

        $this->assertArrayHasKey('receipt_download_url', $content);
    }

    public function testSaveCustomSerialNumberReceiptAndFetch()
    {
        $this->testMakePaymentReceiptEnabledCustomSerialEnabled();

        $payment = $this->getDbLastEntity('payment');

        $invoice = $this->getDbLastEntity('invoice');

        $invoice->updated_at = Carbon::now()->subMinutes(2)->getTimestamp();
        $invoice->save();

        $this->assertNull($invoice->getReceipt());

        $this->ba->proxyAuth();

        $request = [
            'method' => 'GET',
            'url' => '/v1/payment_pages/' . $payment->getPublicId() . '/receipt',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNull($content['receipt']);

        $request = [
            'method' => 'POST',
            'url' => '/v1/payment_pages/' . $payment->getPublicId() . '/save_receipt',
            'content' => [
                'receipt' => 'thisisatestreceiptvalue'
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $invoice = $this->getDbLastEntity('invoice');

        $invoice->updated_at = Carbon::now()->subMinutes(2)->getTimestamp();
        $invoice->save();

        $request = [
            'method' => 'GET',
            'url' => '/v1/payment_pages/' . $payment->getPublicId() . '/receipt',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['invoice_id'], $invoice->getPublicId());

        $this->assertEquals($content['receipt'], 'thisisatestreceiptvalue');

        $this->assertArrayHasKey('receipt_download_url', $content);
    }

    public function testPaymentPageDetails()
    {
        $this->testPaymentLinkMakePaymentWithOrder();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdatePaymentPageWithWrongCheckoutOptions()
    {
        $this->createPaymentLink();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatePaymentPageWithCheckoutOptionsNoEmail()
    {
        $this->createPaymentLink();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatePaymentPageWithWrongCheckoutOptionsNoPhone()
    {
        $this->createPaymentLink();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBrandColorInPaymentPageHosted()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(35,113,236)', $response->getContent());
    }

    public function testBrandColourInPaymentPageHostedForCustomColor()
    {
        $this->fixtures->merchant->edit('10000000000000', ['brand_color' => '000000']);

        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(0,0,0)', $response->getContent());
    }

    public function testBrandColorInPaymentPageHostedForCustomBrandingOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => $org->getId()]);

        $this->fixtures->edit('org', $org->getId(), ['merchant_styles' => ["checkout_theme_color" => "#97144D"]]);

        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(35,113,236)', $response->getContent());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'entity_type' => 'org',
            'name' => 'org_custom_branding',
        ]);

        Entity::clearHostedCacheForPageId("pl_" . self::TEST_PL_ID);

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(151,20,77)', $response->getContent());
    }

    /**
     * @group support_contact_validation
     */
    public function testCreatePaymentLinkWithAlphabetSupportNumber()
    {
        $this->startTest();
    }

    /**
     * @group support_contact_validation
     */
    public function testCreatePaymentLinkWithSupportNumberLessDigits()
    {
        $this->startTest();
    }

    /**
     * @group support_contact_validation
     */
    public function testCreatePaymentLinkWithSupportNumberLargeDigits()
    {
        $this->startTest();
    }

    public function testZapierPaymentPagePaidWebhook()
    {
        $this->markTestSkipped('to be fixed , failing in payment creation due to receiving dummy card data as per compliance ');

        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->expectWebhookEventWithContents('zapier.payment_page.paid.v1', 'testPaymentPagePaidWebhookEventData');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testNoZapierPaymentButtonPaidWebhook()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'button']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->dontExpectWebhookEvent('zapier.payment_page.paid.v1');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testZapierWebhookNotPresentInEventsList()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertContains("invoice.paid", $response);

        $this->assertNotContains("zapier.payment_page.paid.v1", $response);

        $this->assertNotContains("shiprocket.payment_page.paid.v1", $response);
    }

    public function testNoShiprocketPaymentPagePaidWebhookDefault()
    {
        $this->markTestSkipped('to be fixed , failing in payment creation due to receiving dummy card data as per compliance ');

        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->dontExpectWebhookEvent('shiprocket.payment_page.paid.v1');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testShiprocketPaymentPagePaidWebhookEnabled()
    {
        $this->markTestSkipped('to be fixed , failing in payment creation due to receiving dummy card data as per compliance ');

        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $settings = [
            'partner_webhook_settings' => [
                'partner_shiprocket' => "1",
            ]
        ];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $order = $data['payment_link_order']['order'];

        $this->expectWebhookEventWithContents('shiprocket.payment_page.paid.v1', 'testShiprocketPaymentPagePaidWebhookEventData');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testShiprocketPaymentPagePaid1CCWebhookEnabled()
    {
        $notes = $this->getNotes();

        $customerDetials = $this->getCustomerDetails();

        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page'],
            [Order\Entity::NOTES => $notes,'one_click_checkout' => '1', 'customer_details' => $customerDetials]);

        $paymentLink = $data['payment_link'];

        $settings = [
            "one_click_checkout" => '1',
            'partner_webhook_settings' => [
                'partner_shiprocket' => "1",
            ]
        ];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $order = $data['payment_link_order']['order'];

        $this->expectWebhookEventWithContents('shiprocket.payment_page.paid.v1', 'testShiprocketPaymentPagePaid1CCWebhookEventData');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order, Payment\Status::CAPTURED, $notes);
    }

    public function testFetchPaymentsForPaymentPage()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testSendPaymentPageReceipt()
    {
        $this->testMakePaymentReceiptEnabledCustomSerialEnabled();

        $this->ba->proxyAuth();

        $payment = $this->getDbLastEntity('payment');

        $url = '/payment_pages/'.$payment->getPublicId().'/send_receipt';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        $order = $payment->order;

        $invoice = $order->invoice;

        $this->assertEquals('thisisareceipt', $invoice->getReceipt());
    }

    public function testUploadPaymentPageImages()
    {
        $this->ba->proxyAuth();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();
    }

    public function testPaymentPageItemUpdate()
    {
        $this->createPaymentLinkAndOrderForThat();

        $paymentPageItem = $this->getDbEntityById('payment_page_item', 'ppi_10000000000ppi');

        $item = $paymentPageItem->item;

        $this->assertNull($paymentPageItem->getStock());

        $this->assertEquals(5000, $item->getAmount());

        $this->ba->proxyAuth();

        $this->startTest();

        $paymentPageItem = $this->getDbEntityById('payment_page_item', 'ppi_10000000000ppi');

        $item = $paymentPageItem->item;

        $this->assertEquals(2, $paymentPageItem->getStock());

        $this->assertEquals(7500, $item->getAmount());
    }

    /**
     * @group pp_line_item_amount
     */
    public function testCreatePaymentPageOrderWithFloatAmountShouldThrowValidationError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();
        $this->startTest();
    }

    /**
     * @group pp_line_item_amount
     */
    public function testCreatePaymentPageOrderWithOutOfScopeIntegerAmountShouldThrowValidationError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();
        $this->startTest();
    }

    /**
     * @group pp_line_item_amount
     */
    public function testCreatePaymentPageOrderWithOutAmountShouldThrowValidationError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();
        $this->startTest();
    }

    public function testSettingsInPaymentPageItemsInPaymentButton()
    {
        $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'button']);

        $item = $this->createPaymentPageItem();
        $settings = [
            PaymentLink\PaymentPageItem\Entity::POSITION => '0'
        ];

        $item->getSettingsAccessor()->upsert($settings)->save();
        $this->startTest();
    }

    public function testSettingsInPaymentPageItemsInSubscriptionButton()
    {
        $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'subscription_button']);

        $item = $this->createPaymentPageItem();

        $settings = [
            PaymentLink\PaymentPageItem\Entity::POSITION => '0'
        ];

        $item->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    /**
     * @group nocode_pp_order_notes
     */
    public function testOrderCreateShowStoreNotes()
    {
        $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);

        $ppis = [
            [
                PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                    Item\Entity::AMOUNT => 5000,
                ]
            ]
        ];

        $this->createPaymentPageItems(self::TEST_PL_ID, $ppis);

        $this->startTest();
    }

    public function testCreatePaymentPageWithYoutubeVideoInDescription()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreatePaymentPageWithVimeoVideoInDescription()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreatePaymentPageWithOtherVideoInDescription()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testCreatePaymentPageWithDonationGoalTrackerShouldBeReturnedInDetailsApi()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "0"
                ]
            ]
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();
        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testCreatePaymentPageWithDonationGoalTrackerAmountBasedSuccessfully()
    {
        $data = $this->startTest();
        $this->ba->proxyAuth();
        $pl = $this->getDbEntityById('payment_link', $data['id']);
        $resDataSubSet = [
            Entity::GOAL_TRACKER        => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::COLLECTED_AMOUNT        => "0",
                    Entity::SUPPORTER_COUNT         => "0"
                ]
            ]
        ];
        $this->assertDonationGoalTracker($pl, $resDataSubSet);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testCreatePaymentPageWithDonationGoalTrackerSupporterBasedSuccessfully()
    {
        $data = $this->startTest();
        $this->ba->proxyAuth();
        $pl = $this->getDbEntityById('payment_link', $data['id']);
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::AVALIABLE_UNITS         => "10000",
                    Entity::DISPLAY_AVAILABLE_UNITS => "1",
                    Entity::DISPLAY_SOLD_UNITS      => "1",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "0",
                    Entity::SUPPORTER_COUNT         => "0"
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerAmountBasedOnMakePaymentShouldIncrementKeys()
    {
        [$pl, $_, $payment] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page']
        );
        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "2",
                    Entity::SUPPORTER_COUNT         => "1",
                    Entity::COLLECTED_AMOUNT        => "15000",
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);


        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = "0";

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 15000
        ]);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerAmountBasedOnMultipleOrderMakePaymentShouldIncrementKeys()
    {
        [$pl, $order, $_] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page']
        );

        // total 6 payments
        for ($i = 0; $i<5; $i++)
        {
            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);
            $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));
        }

        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "17",
                    Entity::SUPPORTER_COUNT         => "6",
                    Entity::COLLECTED_AMOUNT        => stringify(15000 + (5000 * 5) + (10000 * 5 * 2)),
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "14";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "5";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = stringify(15000 + (5000 * 4) + (10000 * 4 * 2));

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 25000
        ]);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerSupporterBasedOnMakePaymentShouldIncrementKeys()
    {
        [$pl, $_, $payment] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page'],
            [
                Entity::AVALIABLE_UNITS         => "10000",
                Entity::DISPLAY_AVAILABLE_UNITS => "1",
                Entity::DISPLAY_SOLD_UNITS      => "1",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ],
            PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED
        );
        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "2",
                    Entity::SUPPORTER_COUNT         => "1",
                    Entity::COLLECTED_AMOUNT        => "15000",
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = "0";

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 15000
        ]);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerSupporterBasedOnMultipleOrderMakePaymentShouldIncrementKeys()
    {
        [$pl, $order, $_] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page'],
            [
                Entity::AVALIABLE_UNITS         => "10000",
                Entity::DISPLAY_AVAILABLE_UNITS => "1",
                Entity::DISPLAY_SOLD_UNITS      => "1",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ],
            PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED
        );

        // total 6 payments
        for ($i = 0; $i<5; $i++)
        {
            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);
            $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));
        }

        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "27",
                    Entity::SUPPORTER_COUNT         => "6",
                    Entity::COLLECTED_AMOUNT        => stringify(15000 + (5000 * 5 * 3) + (10000 * 5 * 2)),
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "22";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "5";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = stringify(15000 + (5000 * 4 * 3) + (10000 * 4 * 2));

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 35000
        ]);
    }

    /**
     * @group nocode_pp_subscription
     */
    public function testCreateSubscriptionButton()
    {
        $subscriptionModule = $this->app['module']->subscription;

        $mockSubscription   = \Mockery::mock($subscriptionModule)->makePartial();

        $mockSubscription->shouldReceive('fetchPlan')->andReturn([
            PaymentLink\PaymentPageItem\Entity::ITEM    => [
                Item\Entity::NAME           => "amount",
                Item\Entity::AMOUNT         => 100000,
                Item\Entity::CURRENCY       => Currency::INR,
                Item\Entity::DESCRIPTION    => "SAMPLE DESCRIPTION"
            ],
            "interval"  => 23,
            "period"    => 23,
        ]);

        $this->app['module']->subscription = $mockSubscription;

        $this->startTest();
    }

    /**
     * @group nocode_pp_subscription
     */
    public function testCreateSubscription()
    {
        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID, [
            'view_type' => 'subscription_button'
        ]);

        $this->createSubscriptionPaymentPageItem();

        $subscriptionModule = $this->app['module']->subscription;

        $mockSubscription   = \Mockery::mock($subscriptionModule)->makePartial();

        $mockSubscription->shouldReceive('createSubscription')->andReturn(["id" => 'plan_' . self::TEST_PLAN_ID]);

        $this->app['module']->subscription = $mockSubscription;

        $this->startTest();
    }

    /**
     * @group nocode_pp_subscription
     */
    public function testCreateSubscriptionWithNoPlanIdShouldThrowException()
    {
        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID, [
            'view_type' => 'subscription_button'
        ]);

        $this->createPaymentPageItem();

        $this->startTest();
    }

    public function testPaymentHandleCreationL1Activation()
    {
        $this->activateMerchantToTriggerPaymentHandleCreation();

        $this->ba->proxyAuthLive();

        $ph = $this->getDbLastEntity('payment_link', MODE::LIVE);

        $this->assertNotNull($ph);

        $this->assertEquals('Test Label 123', $ph[Entity::TITLE]);

        $this->assertEquals('@testlabel123', $ph->getSlugFromShortUrl());
    }

    public function testPaymentHandleCreationApi()
    {
        $this->mockGimliPaymentHandle('Test Merchant');

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testPaymentHandleCreationApiCallAfterActivation()
    {
        $this->activateMerchantToTriggerPaymentHandleCreation();

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testPaymentHandleCreationWhenPaymentHandleAlreadyExists()
    {
        $this->makeRequestToCreatePaymentHandle();

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testPaymentHandleCreationBillingLabelLengthMoreThanThirty()
    {
        $this->ba->proxyAuthLive();

        $this->mockGimliPaymentHandle('Test Billing Label Private Limited');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'Test Billing Label Private Limited']);

        $this->startTest();
    }

    public function testPaymentHandleCreationFromTestMode()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testPaymentHandleCreationBillingLabelLengthMoreThanEighty()
    {
        $this->ba->proxyAuthLive();

        $this->mockGimliPaymentHandle('Test Billing Label Private Limited Lorem Ipsum is simply dummy text of the printing Lorem Ipsum');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'Test Billing Label Private Limited Lorem Ipsum is simply dummy text of the printing Lorem Ipsum']);

        $this->startTest();
    }

    public function testPaymentHandlePrecreation()
    {
        $this->mockGimliPaymentHandle();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPaymentHandleCreationWithDashInBillingLabel()
    {
        // billing label with -
        $billingLabel = 'Test Label-123';

        $this->activateMerchantToTriggerPaymentHandleCreation($billingLabel);

        $this->ba->proxyAuthLive();

        $ph = $this->getDbLastEntity('payment_link', MODE::LIVE);

        $this->assertNotNull($ph);

        $this->assertEquals('Test Label-123', $ph[Entity::TITLE]);

        $this->assertEquals('@testlabel-123', $ph->getSlugFromShortUrl());
    }

    public function testPaymentHandleCreationWithBillingLabelLengthLessThanFour()
    {
        $billingLabel = 'a';

        // merchant detail internally creates merchant entity
        $this->fixtures->edit('merchant', self::TEST_MID, ['billing_label'  => $billingLabel]);

        $this->mockGimliPaymentHandle($billingLabel);

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testCreatePaymentPageWithMandatoryPayerNameAndExpiry()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $expireBy = Carbon::now(Timezone::IST)->addDays(30)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['expire_by'] = $expireBy;

        $this->startTest();
    }

    public function testCreatePaymentPageWithMandatoryPayerNameAndExpiryWithoutPayerName()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $this->startTest();
    }


    public function testCreatePaymentPageWithMandatoryPayerNameAndExpiryWithoutExpiry()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $this->startTest();
    }

    // updating expiry should work as payer_name is not present in original PP
    public function testCreatePaymentPageUpdateWithPositive()
    {
         $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            'expire_by' => null,
            'view_type' => 'page'
        ]);

        $this->startTest();
    }

    // update should work as payer_name is present, but patch request does has udf schema
    public function testCreatePaymentPageUpdateWithPositive2()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            'expire_by' => null,
            'view_type' => 'page',
        ]);

        $settings = [
            "partner_webhook_settings" => [
                "partner_shiprocket" => "1",
            ],
            "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }


    // update should work as payer_name is present, patch request tries to set expiry_by to a timestamp
    public function testCreatePaymentPageUpdateWithPositive3()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            'expire_by' => null,
            'view_type' => 'page',
        ]);

        $settings = [
            "partner_webhook_settings" => [
                "partner_shiprocket" => "1",
            ],
            "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();

        $expireBy = Carbon::now(Timezone::IST)->addDays(30)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['expire_by'] = $expireBy;

        $this->startTest();
    }

    // update should not work as payer_name is present, request contains udf schema but not payer name
    public function testCreatePaymentPageUpdateWithNegative()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            'expire_by' => null,
            'view_type' => 'page',
        ]);

        $settings = [
            "partner_webhook_settings" => [
                "partner_shiprocket" => "1",
            ],
            "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();

        $expireBy = Carbon::now(Timezone::IST)->addDays(30)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['expire_by'] = $expireBy;

        $this->startTest();
    }


    // update should work as payer_name is present, request tries to set expires_by to no_expiry
    public function testCreatePaymentPageUpdateWithNegative2()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::ENABLE_PAYER_NAME_FOR_PP,
        ]);


        $this->fixtures->create('feature', [
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
            'name' => Constants::HIDE_NO_EXPIRY_FOR_PP
        ]);

        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            'expire_by' => null,
            'view_type' => 'page',
        ]);

        $settings = [
            "partner_webhook_settings" => [
                "partner_shiprocket" => "1",
            ],
            "udf_schema" => "[{\"name\":\"payer__name\",\"required\":true,\"title\":\"Customer_Name\",\"type\":\"string\",\"settings\":{\"position\":1}},{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }



    public function testPaymentHandleUpdate()
    {
        $this->testPaymentHandleCreationApi();

        $pl = $this->getDbLastEntity('payment_link', 'live');

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->mockGimliPaymentHandle();

        $this->startTest();
    }

    public function testPaymentHandleUpdateOld()
    {
        $this->mockGimliPaymentHandle("Test Billing Label");

        $this->ba->proxyAuthLive();

        $createRequest = [
            'method' => 'POST',
            'url'    => '/v1/payment_handle'
        ];

        $phCreateResponse = $this->makeRequestAndGetContent($createRequest);

        $plId = $phCreateResponse['id'];

        $newPaymentHandle = "@newHandle";

        $this->mockGimliPaymentHandle($newPaymentHandle);

        $handleUrl = $this->app['config']->get('app.payment_handle_hosted_base_url')
            . "/". $newPaymentHandle;

        $request = [
            'method' => 'PATCH',
            'url' => '/v1/payment_handle/' . $plId,
            'content' => [
                'slug' => $newPaymentHandle
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(Entity::URL, $content);

        $this->assertArrayHasKey(Entity::SLUG, $content);

        $this->assertEquals($content[Entity::SLUG], $newPaymentHandle);

        $this->assertEquals($content[Entity::URL], $handleUrl);
    }

    //@ missing in slug
    public function testPaymentHandleUpdateWithWrongSlug()
    {
        $this->makeRequestToCreatePaymentHandle();

        $this->ba->proxyAuthLive();

        $this->startTest();

    }

    public function testPaymentHandleUpdateSlugLengthLessThanFour()
    {
        $this->makeRequestToCreatePaymentHandle();

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testPaymentHandleUpdateSlugLengthGreaterThanThirty()
    {
        $this->makeRequestToCreatePaymentHandle();

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testPaymentHandleSuggestionApiWithBillingLabelLengthMoreThanThirty()
    {
        $this->mockGimliPaymentHandle();

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'Handle Greater Than Thirty Characters Private Limited']);

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testPaymentHandleFetch()
    {
        $this->makeRequestToCreatePaymentHandle();

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testPaymentHandleFetchWhenHandleDoesNotExists()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPaymentHandleDeactivatedView()
    {
        $this->activateMerchantToTriggerPaymentHandleCreation('ANC Corp');

        $this->ba->proxyAuthLive();

        // activating merchant to make live request
        $this->fixtures->merchant->activate('10000000000000');

        // getting pl id for handle
        $paymentHandle = $this->getDbLastEntity('payment_link', 'live');

        $this->fixtures->on('live')->edit('payment_link', $paymentHandle[Entity::ID], [ 'status' => 'inactive' , 'status_reason' => 'deactivated']);

        // calling view get on deactivated payment handle
        $view = $this->call('GET', "/v1/payment_pages/pl_" . $paymentHandle[Entity::ID] . "/view");;

        $view->assertStatus(200);

        $this->assertStringContainsString('payment-handle/error.js', $view->getContent());
    }

    public function testPaymentHandleCreationPrecreateCalled()
    {
        $this->mockGimliPaymentHandle();
        // make precreate api call
        $this->ba->proxyAuth();

        $request = [
            'method' => 'POST',
            'url' => '/v1/precreate_payment_handle',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(Entity::SLUG, $content);

        $this->ba->proxyAuthLive();

        $this->fixtures->merchant->activate('10000000000000');

        $this->startTest();
    }

    public function testPaymentHandleCreationPrecreateNotCalled()
    {
        $this->mockGimliPaymentHandle();

        $this->ba->proxyAuthLive();

        $this->fixtures->merchant->activate('10000000000000');

        $this->startTest();
    }

    public function testPaymentHandleUpdateAtPrecreateState()
    {
        $this->testPaymentHandlePrecreation();

        $this->mockGimliPaymentHandle();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPaymentHandleGetAtPrecreateState()
    {
        $this->testPaymentHandlePrecreation();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPaymentHandleEncryptCustomAmount()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testUpdatePageWithGoalTrackerInactiveAndEndDatePastShouldNotThrowValidationError()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $this->createPaymentPageItem();
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "0",
                Entity::META_DATA       => [
                    Entity::DISPLAY_AVAILABLE_UNITS => "0",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "0",
                    Entity::DISPLAY_SOLD_UNITS      => "0",
                    Entity::AVALIABLE_UNITS         => "55",
                    Entity::GOAL_END_TIMESTAMP      => (string) (new Carbon())->subDays(1)->getTimestamp()
                ]
            ]
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();
        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testUpdatePageWithGoalTrackerActiveEndDatePastShouldThrowValidationError()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $this->createPaymentPageItem();
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::DISPLAY_AVAILABLE_UNITS => "0",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "0",
                    Entity::DISPLAY_SOLD_UNITS      => "0",
                    Entity::AVALIABLE_UNITS         => "55",
                    Entity::GOAL_END_TIMESTAMP      => (string) (new Carbon())->subDays(1)->getTimestamp()
                ]
            ]
        ];

         $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    /**
     * @group nocode_pp_create_dedupe
     * @return void
     */
    public function testOnPaymentPageCreateDedupeCallIsDispatchedInLiveMode()
    {
        $this->markTestSkipped("On laravel version upgrade fails. Test case can be skipped, does not have major implication");

        $this->ba->proxyAuthLive();

        Bus::fake();

        $this->startTest();

        Bus::assertDispatched(PaymentPageProcessor::class);
    }

    /**
     * @group nocode_pp_create_dedupe
     * @return void
     */
    public function testOnPaymentPageCreateDedupeCallIsNotDispatchedInTestMode()
    {
        $this->markTestSkipped("On laravel version upgrade fails. Test case can be skipped, does not have major implication");

        $this->ba->proxyAuthTest();

        Bus::fake();

        $this->startTest();

        Bus::assertNotDispatched(PaymentPageProcessor::class, function (PaymentPageProcessor $processor) {
            return $processor->getEvent() !== PaymentPageProcessor::PAYMENT_PAGE_HOSTED_CACHE;
        });
    }

    /**
     * @group pp_shiprocket
     */
    public function testCreatePaymentPageWithShiprocketEnabledWithoutShiprocketFieldShouldThrowError()
    {
        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testCreatePaymentPageWithShiprocketDisabledWithoutShiprocketFieldShouldPass()
    {
        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testCreatePaymentPageWithShiprocketEnabledWithShiprocketFieldShouldPass()
    {
        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testCreatePaymentPageWithPartnerSettingsAndInvalidPartnerShouldThrowError()
    {
        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testCreatePaymentPageWithPartnerSettingsAndInvalidPartnerValueShouldThrowError()
    {
        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testUpdatePaymentPageWithShiprocketEnabledWithoutShiprocketFieldShouldThrowError()
    {
        $this->setupPartnerWebhookSettingTestCase([
            Entity::PARTNER_SHIPROCKET => "1",
        ], false);

        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testUpdatePaymentPageWithShiprocketDisabledWithoutShiprocketFieldShouldPass()
    {
        $this->setupPartnerWebhookSettingTestCase([
            Entity::PARTNER_SHIPROCKET => "0",
        ], false);

        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testUpdatePaymentPageWithShiprocketEnabledWithShiprocketFieldShouldPass()
    {
        $this->setupPartnerWebhookSettingTestCase([
            Entity::PARTNER_SHIPROCKET => "1",
        ]);

        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testUpdatePaymentPageWithPartnerSettingsAndInvalidPartnerShouldThrowError()
    {
        $this->setupPartnerWebhookSettingTestCase([
            Entity::PARTNER_SHIPROCKET => "1",
        ]);

        $this->startTest();
    }

    /**
     * @group pp_shiprocket
     */
    public function testUpdatePaymentPageWithPartnerSettingsAndInvalidPartnerValueShouldThrowError()
    {
        $this->setupPartnerWebhookSettingTestCase([
            Entity::PARTNER_SHIPROCKET => "1",
        ]);

        $this->startTest();
    }

    /**
     * @group nocode_invalid_character
     * @return void
     */
    public function testUpdatePaymentPageWithInvalidCharacterInTitleThrowsError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_invalid_character
     * @return void
     */
    public function testUpdatePaymentPageWithInvalidCharacterInDescriptionThrowsError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_invalid_character
     * @return void
     */
    public function testUpdatePaymentPageWithInvalidCharacterInPaymentSuccessMessageThrowsError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_invalid_character
     * @return void
     */
    public function testCreatePaymentPageWithInvalidCharacterInTitleThrowsError()
    {
        $this->startTest();
    }

    /**
     * @group nocode_invalid_character
     * @return void
     */
    public function testCreatePaymentPageWithInvalidCharacterInDescriptionThrowsError()
    {
        $this->startTest();
    }

    /**
     * @group nocode_invalid_character
     * @return void
     */
    public function testCreatePaymentPageWithInvalidCharacterInPaymentSuccessMessageThrowsError()
    {
        $this->startTest();
    }

    /**
     * @group pp_captured_payment_count
     */
    public function testOnMultipleOrderMakePaymentShouldUpdateCapturedPaymentCount()
    {
        $data = $this->createPaymentLinkAndOrderForThat();

        $pl = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $order->getId()));

        // total 6 payments
        for ($i = 0; $i<5; $i++)
        {
            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);

            $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));
        }

        // get latest instance
        $pl = $this->getDbEntityById('payment_link', $pl->getId());

        $computed = $pl->getComputedSettings()->toArray();

        $this->assertEquals($computed[PaymentLink\Entity::CAPTURED_PAYMENTS_COUNT], '6');
    }

    /**
     * @group pp_captured_payment_count
     */
    public function testOnGetDetailsCallAndNoCapturedPaymentCountShouldUpdateCapturedPaymentCount()
    {
        $data = $this->createPaymentLinkAndOrderForThat();

        $pl = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $order->getId()));

        // get latest instance
        $pl = $this->getDbEntityById('payment_link', $pl->getId());

        // remove the computed settings for captured payment count
        $computed = $pl->getComputedSettings()->toArray();

        unset($computed[PaymentLink\Entity::CAPTURED_PAYMENTS_COUNT]);

        $pl->getComputedSettingsAccessor()->upsert($computed)->save();

        $this->startTest();
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnHostedViewCallCachingShouldWork()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        Event::fake(false);

        $id = "pl_" . self::TEST_PL_ID;

        $this->callViewUrlAndMakeAssertions();

        Event::assertDispatched(CacheMissed::class);

        $this->callViewUrlAndMakeAssertions();

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) use ($id) {
            return $hit->key === Entity::getHostedCacheKey($id);
        });

        Entity::clearHostedCacheForPageId($id);
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnCreatePaymentPageViewCallShouldBeCached()
    {
        Event::fake(false);

        $data = $this->startTest();

        $this->callViewUrlAndMakeAssertions(Entity::stripDefaultSign($data['id']));

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) use ($data) {
            return $hit->key === Entity::getHostedCacheKey($data['id']);
        });
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnUpdatePaymentPageViewCallShouldBeCached()
    {
        Event::fake(false);

        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $data = $this->startTest();

        $this->callViewUrlAndMakeAssertions(Entity::stripDefaultSign($data['id']));

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) use ($data) {
            return $hit->key === Entity::getHostedCacheKey($data['id']);
        });
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnPageActivateViewCallShouldBeCached()
    {
        Event::fake(false);

        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::DEACTIVATED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $data = $this->startTest();

        $this->callViewUrlAndMakeAssertions(Entity::stripDefaultSign($data['id']));

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) use ($data) {
            return $hit->key === Entity::getHostedCacheKey($data['id']);
        });
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnPageExpireViewCallShouldBeCached()
    {
        Event::fake();

        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, ['expire_by' => '1400000000']);

        $this->fixtures->create('payment_link');

        $this->ba->cronAuth();

        $this->startTest();

        $this->callViewUrlAndMakeAssertions();

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) {
            return $hit->key === Entity::getHostedCacheKey('pl_' . self::TEST_PL_ID);
        });
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnPageSetRecieptDetailsViewCallShouldBeCached()
    {
        Event::fake(false);

        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]'
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();

        $this->callViewUrlAndMakeAssertions();

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) {
            return $hit->key === Entity::getHostedCacheKey('pl_' . self::TEST_PL_ID);

        });
    }

    /**
     *  @group pp_hosted_cache
     */
    public function testOnPageUpdateItemViewCallShouldBeCached()
    {
        Event::fake(false);

        $this->createPaymentLinkAndOrderForThat();

        $this->ba->proxyAuth();

        $this->startTest();

        $this->callViewUrlAndMakeAssertions();

        Event::assertDispatched(CacheHit::class, function(CacheHit $hit) {
            return $hit->key === Entity::getHostedCacheKey('pl_' . self::TEST_PL_ID);
        });
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testGoalTrackerAmountMoreThenACrShouldbeAllowed()
    {
        $data = $this->startTest();
        $this->ba->proxyAuth();
        $pl = $this->getDbEntityById('payment_link', $data['id']);
        $resDataSubSet = [
            Entity::GOAL_TRACKER        => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "750000000000000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::COLLECTED_AMOUNT        => "0",
                    Entity::SUPPORTER_COUNT         => "0"
                ]
            ]
        ];
        $this->assertDonationGoalTracker($pl, $resDataSubSet);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testGoalTrackerAmountMoreThenACrOnMakingMultiplePaymentShouldIncrementKeys()
    {
        [$pl, $order, $_] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page'],
            [
                Entity::GOAL_AMOUNT             => "750000000000000",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ]
        );

        // total 6 payments
        for ($i = 0; $i<5; $i++)
        {
            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);
            $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));
        }

        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "750000000000000",
                    Entity::COLLECTED_AMOUNT        => stringify(15000 + (5000 * 5) + (10000 * 5 * 2)),
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "14";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "5";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = stringify(15000 + (5000 * 4) + (10000 * 4 * 2));

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 25000
        ]);
    }

    /**
     * @group pp_payment_amount_quantity_check
     * @return void
     */
    public function testOnPaymentFlowValidateLatestAmount()
    {
        $this->assertManipulateOrderItemAndMakePayment(PaymentLink\Core::AMOUT_QUANTITY_TAMPERED);
    }

    /**
     * @group pp_payment_amount_quantity_check
     * @return void
     */
    public function testOnPaymentFlowValidateLatestAmountWithFixedAmountNotMandatory()
    {
        $attributes = [
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => false,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::NAME => 'Fixed',
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => false,
                    PaymentLinkModel\PaymentPageItem\Entity::MIN_AMOUNT   => 100,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                        Item\Entity::NAME => 'Variable',
                    ]
                ]
            ]
        ];

        $this->assertManipulateOrderItemAndMakePayment(
            PaymentLink\Core::AMOUT_QUANTITY_TAMPERED,
            1000,
            $attributes
        );
    }

    /**
     * @group pp_payment_amount_quantity_check
     * @return void
     */
    public function testOnPaymentFlowValidateLatestAmountWithFixedAmountMandatory()
    {
        $attributes = [
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => true,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::NAME => 'Fixed',
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => true,
                    PaymentLinkModel\PaymentPageItem\Entity::MIN_AMOUNT   => 100,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                        Item\Entity::NAME => 'Variable',
                    ]
                ]
            ]
        ];

        $this->assertManipulateOrderItemAndMakePayment(
            PaymentLink\Core::AMOUT_QUANTITY_TAMPERED,
            1000,
            $attributes
        );
    }

    /**
     * @group pp_payment_amount_quantity_check
     * @return void
     */
    public function testOnPaymentFlowValidateLatestAmountWithOrderLineItemReduced()
    {
        $attributes = [
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => true,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::NAME => 'Fixed',
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => true,
                    PaymentLinkModel\PaymentPageItem\Entity::MIN_AMOUNT   => 100,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                        Item\Entity::NAME => 'Variable',
                    ]
                ]
            ]
        ];

        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $order  = $data['payment_link_order']['order'];

        $order->lineItems[1]->delete();

        $page   = $data['payment_link'];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectErrorMessage(PaymentLink\Core::AMOUT_QUANTITY_TAMPERED);

        $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $page,
            $this->getDbEntityById('order', $order->getId())
        );
    }

    /**
     * @group pp_payment_amount_quantity_check
     * @return void
     */
    public function testOnPaymentFlowValidateLatestAmountWithNonMandatoryOrderLineItemReduced()
    {
        $attributes = [
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => true,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::NAME => 'Fixed',
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => false,
                    PaymentLinkModel\PaymentPageItem\Entity::MIN_AMOUNT   => null,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                        Item\Entity::NAME => 'Fixed 1',
                    ]
                ]
            ]
        ];

        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $order  = $data['payment_link_order']['order'];

        $order->lineItems[1]->delete();

        $page   = $data['payment_link'];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectErrorMessage(PaymentLink\Core::AMOUT_QUANTITY_TAMPERED);

        $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $page,
            $this->getDbEntityById('order', $order->getId())
        );
    }

    /**
     * @group pp_ncu
     * @group nocode_ncu
     * @return void
     */
    public function testOnCreateWithSlugNocodeCustomUrlShouldBeCreated()
    {
        $this->ba->proxyAuthLive();

        $data = $this->startTest();

        $this->assertNotNull($data[Entity::SHORT_URL]);
    }

    /**
     * @group pp_ncu
     * @group nocode_ncu
     * @return void
     */
    public function testOnCreateWithExistingDeletedSlugNocodeCustomUrlShouldBeCreated()
    {
        $hostedUrl  = config()->get('app.payment_link_hosted_base_url');
        $domain     = PaymentLink\NocodeCustomUrl\Entity::determineDomainFromUrl($hostedUrl);
        $slug       = 'testslug12';
        $entity     = new PaymentLink\NocodeCustomUrl\Entity;

        $entity->generateAndSetUniqueId();

        $input = [
            PaymentLink\NocodeCustomUrl\Entity::ID              => $entity->getId(),
            PaymentLink\NocodeCustomUrl\Entity::MERCHANT_ID     => '10000000000000',
            PaymentLink\NocodeCustomUrl\Entity::SLUG            => $slug,
            PaymentLink\NocodeCustomUrl\Entity::DELETED_AT      => Carbon::now()->addHours(-1)->getTimestamp(),
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN          => $domain,
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT_ID      => self::TEST_PL_ID_2,
            PaymentLink\NocodeCustomUrl\Entity::PRODUCT         => PaymentLink\ViewType::PAGE,
            PaymentLink\NocodeCustomUrl\Entity::META_DATA       => [],
        ];

        $this->fixtures->create('nocode_custom_url', $input);

        $this->ba->proxyAuthLive();

        $data = $this->startTest();

        $this->assertNotNull(array_get($data, Entity::SHORT_URL));
    }

    /**
     * @group pp_payment_amount_quantity_check
     * @return void
     */
    public function testOnPaymentFlowValidateLatestAmountWithCustomDecidedAmountMandatoryShouldPass()
    {
        $defaultPaymentLinkAttribute = [
            PaymentLinkModel\Entity::ID     => self::TEST_PL_ID,
            PaymentLinkModel\Entity::AMOUNT => null,
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLinkModel\PaymentPageItem\Entity::MANDATORY   => true,
                    PaymentLinkModel\PaymentPageItem\Entity::MIN_AMOUNT   => 100,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                        Item\Entity::NAME => 'Variable',
                    ]
                ]
            ]
        ];

        $paymentPageItemsAttribute = array_pull($defaultPaymentLinkAttribute, PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS, []);

        $page = $this->createPaymentLink(self::TEST_PL_ID, $defaultPaymentLinkAttribute);

        $this->createPaymentPageItems(self::TEST_PL_ID, $paymentPageItemsAttribute);

        $totalAmount = 10000;

        $orderAttribute = [
            'amount' => $totalAmount,
            Order\Entity::PAYMENT_CAPTURE => true,
        ];

        $order = $this->fixtures->create('order', $orderAttribute);

        $itemForLineItemAttributes = [
            Item\Entity::ID     => UniqueIdEntity::generateUniqueId(),
            Item\Entity::AMOUNT => $totalAmount
        ];

        $itemForLineItem = $this->fixtures->create('item', $itemForLineItemAttributes);

        $this->fixtures->create('line_item', [
            LineItem\Entity::ID          => self::TEST_PPI_ID,
            LineItem\Entity::ITEM_ID     => $itemForLineItem->getId(),
            LineItem\Entity::REF_TYPE    => 'payment_page_item',
            LineItem\Entity::REF_ID      => self::TEST_PPI_ID,
            LineItem\Entity::ENTITY_ID   => $order->getId(),
            LineItem\Entity::ENTITY_TYPE => 'order',
            LineItem\Entity::AMOUNT      => $itemForLineItem->getAmount(),
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $page->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $totalAmount;
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::NOTES]           = [];

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS   => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testCreatePaymentPageWithUdfSchemaPatternInvalidShouldFail()
    {
        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testUpdatePaymentPageWithUdfSchemaPatternInvalidShouldFail()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testCreatePaymentPageWithUdfSchemaTypeInvalidShouldFail()
    {
        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testUpdatePaymentPageWithUdfSchemaTypeInvalidShouldFail()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testCreatePaymentPageWithUdfSchemaOptionCmpInvalidShouldFail()
    {
        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testUpdatePaymentPageWithUdfSchemaOptionCmpInvalidShouldFail()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testCreatePaymentPageWithUdfSchemaXssShouldFail()
    {
        $this->startTest();
    }

    /**
     * @group nocode_pp_udf
     * @return void
     */
    public function testUpdatePaymentPageWithUdfSchemaXssShouldFail()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();

        $this->startTest();
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testCreatePaymentPageWithCustomDomain()
    {

        $this->ba->proxyAuthLive();

        Config::set('app.nocode.cache.custom_url_ttl', 0);

        $this->startTest();

        $pl     = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $ncu    = $this->getDbLastEntity("nocode_custom_url", Mode::LIVE);

        $this->assertEquals("https://cds.razorpay.in/myslug", $pl->getShortUrl());

        $this->assertEquals("cds.razorpay.in", $ncu->getDomain());

        $this->assertEquals("myslug", $ncu->getSlug());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomain()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/myslug",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "myslug",
        ], $pl);

        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $this->startTest();

        $pl     = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $ncu    = $this->getDbLastEntity("nocode_custom_url", Mode::LIVE);

        $this->assertEquals("https://cds1.razorpay.in/myslug1", $pl->getShortUrl());
        $this->assertEquals("cds1.razorpay.in", $ncu->getDomain());
        $this->assertEquals("myslug1", $ncu->getSlug());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testCreatePaymentPageWithCustomDomainEmptySlug()
    {
        $this->ba->proxyAuthLive();
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->startTest();

        $pl     = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $ncu    = $this->getDbLastEntity("nocode_custom_url", Mode::LIVE);

        $this->assertEquals("https://cds.razorpay.in/", $pl->getShortUrl());
        $this->assertEquals("cds.razorpay.in", $ncu->getDomain());
        $this->assertEquals("", $ncu->getSlug());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomainEmptySlugAnotherCustomDomain()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "",
        ], $pl);
        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $this->startTest();

        $pl     = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $ncu    = $this->getDbLastEntity("nocode_custom_url", Mode::LIVE);

        $this->assertEquals("https://random.razorpay.in/", $pl->getShortUrl());
        $this->assertEquals("random.razorpay.in", $ncu->getDomain());
        $this->assertEquals("", $ncu->getSlug());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomainToRzpDomainWithSlug()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/muslug",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "myslug",
        ], $pl);
        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $this->startTest();

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->assertNotEquals("https://cds.razorpay.in/muslug", $pl->getShortUrl());
        $this->assertStringNotContainsString("cds.razorpay.in", $pl->getShortUrl());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomainToRzpDomainWithOutSlug()
    {
        Config::set('app.nocode.cache.custom_url_ttl', 0);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/muslug",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "myslug",
        ], $pl);
        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $this->startTest();

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->assertNotEquals("https://cds.razorpay.in/muslug", $pl->getShortUrl());
        $this->assertStringNotContainsString("cds.razorpay.in", $pl->getShortUrl());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testCreatePaymentPageWithCustomDomainNoSlugError()
    {
        $this->ba->proxyAuthLive();
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->startTest();
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testCreatePaymentPageWithOutCustomDomainEmptySlugError()
    {
        $this->ba->proxyAuthLive();
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->startTest();
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomainNoSlugError()
    {
        Config::set('app.nocode.cache.custom_url_ttl', 0);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/muslug",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "myslug",
        ], $pl);
        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomainEmptySlugToRzpDomainNoSlug()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "",
        ], $pl);
        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $this->startTest();

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->assertStringNotContainsString("cds.razorpay.in", $pl->getShortUrl());
    }

    /**
     * @group nocode_cds
     * @return void
     */
    public function testUpdatePaymentPageWithCustomDomainEmptySlugToDetailsShouldReturnEmptySlug()
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        Config::set('app.nocode.cache.custom_url_ttl', 0);
        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL => "https://cds.razorpay.in/",
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);
        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN => "cds.razorpay.in",
            PaymentLink\NocodeCustomUrl\Entity::SLUG => "",
        ], $pl);
        $settings["custom_domain"] = "cds.razorpay.in";
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->ba->proxyAuthLive();

        $res = $this->startTest();

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->assertEquals("", $res[PaymentLink\Entity::SLUG]);
        $this->assertEquals("", $pl->getSlugFromShortUrl());
    }

    /**
     * @group nocode_cds
     */
    public function testOnCreateCustomDomainShouldWhitelistDomain()
    {
        $domain = "subdomain.razorpay.com";

        $plans = $this->createCDSPlan();

        $planId = $plans[0]->getId();

        $this->ba->proxyAuth();

        $this->startTest(['request' => ['content' => [
            "domain_name" => $domain,
            'plan_id'     => $planId
        ]]]);

        /**
         * @var $merchant \RZP\Models\Merchant\Entity
         */
        $merchant = $this->getDbEntityById("merchant", self::TEST_MID);

        $found = false;

        foreach ($merchant->getWhitelistedDomains() as $dm)
        {
            if ($dm === $domain)
            {
                $found = true;
                break;
            }
        }

        $plan = $this->getDbLastEntity('schedule_task', MODE::TEST);

        $this->assertEquals($planId, $plan['schedule_id']);

        $this->assertTrue($found);
    }

    /**
     * @group nocode_cds
     */
    public function testOnDeleteCustomDomainShouldRemoveFromWhitelistDomain()
    {
        $domain = "subdomain.razorpay.com";

        $plans = $this->createCDSPlan();

        $planId = $plans[0]->getId();

        $this->createCustomDomainForMerchantWithPlan($planId, '10000000000000' ,$domain);

        $merchant = $this->getDbEntityById("merchant", self::TEST_MID);

        $merchant->setWhitelistedDomains([$domain]);

        $merchant->saveOrFail();

        $this->startTest(['request' => ['content' => ["domain_name" => $domain]]]);

        $merchant = $this->getDbEntityById("merchant", self::TEST_MID);

        $found = false;

        foreach ($merchant->getWhitelistedDomains() as $dm)
        {
            if ($dm === $domain)
            {
                $found = true;
                break;
            }
        }

        $this->assertFalse($found);
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainServiceCreatePlans()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainServiceCreatePlansDuplicateAlias()
    {
        $this->testCustomDomainServiceCreatePlans();

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainServicePlansGet()
    {
        $this->createCDSPlan();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainServiceDeletePlans()
    {
        $plans =  $this->createCDSPlan();

        $planId = $plans[0]->getId();

        $this->ba->adminAuth();

        $this->startTest(
            [
                'request' => [
                    'content' => [
                        "plan_ids" => [
                            $planId
                        ]
                    ]
                ],
                'response' => [
                    'content' => [
                        'successful' => [
                            $planId
                        ]
                    ]
                ]
            ]);
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainFetchPlanForMerchant()
    {
        $this->testOnCreateCustomDomainShouldWhitelistDomain();

        $this->ba->proxyAuth();

        $request = [
            "url"       => "/v1/payment_pages/cds/plans/plan",
            "method"    => "get",
        ];

        $response = $this->makeRequestAndGetContent($request);

        $plan = array_get($response, 'plan');

        $this->assertArrayKeysExist($plan, ['id','alias','name','period','interval','next_billing_at', 'metadata']);

        $this->assertArrayKeysExist($plan['metadata'], ['per_month_amount', 'plan_amount','discount']);
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainFetchPlanForMerchantWhenPlanDoesNotExist()
    {
        $this->ba->proxyAuth();

        $request = [
            "url"       => "/v1/payment_pages/cds/plans/plan",
            "method"    => "get",
        ];

        $response = $this->makeRequestAndGetContent($request);

        $plan = array_get($response, 'plan');

        $this->assertEquals([], $plan);
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainFetchValidPlanAfterDeletion()
    {
        $this->testCustomDomainPlanDeletionWithDomainDeletionForMerchant();

        $this->ba->proxyAuth();

        $request = [
            "url"       => "/v1/payment_pages/cds/plans/plan",
            "method"    => "get",
        ];

        $response = $this->makeRequestAndGetContent($request);

        $plan = array_get($response, 'plan');

        $this->assertArrayKeysExist($plan, ['id','alias','name','period','interval','next_billing_at', 'metadata']);

        $this->assertArrayKeysExist($plan['metadata'], ['per_month_amount', 'plan_amount','discount']);
    }

    /**
     * @group nocode_cds
     */
    public function testCustomDomainPlanDeletionWithDomainDeletionForMerchant()
    {
        $plans =  $this->createCDSPlan();

        $planId = $plans[0]->getId();

        $this->createCustomDomainForMerchantWithPlan($planId);

        $this->ba->proxyAuth();

        $request = [
            'url' => '/v1/payment_pages/cds/domains',
            'method' => 'delete',
            'content' => [
                'domain_name'      => 'https://subdomain.razorpay.com'
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('domain_name', $response );

        $this->assertArrayHasKey('status', $response);

        $this->assertEquals($response['status'], 'deleted');

        $this->assertEquals($response['domain_name'], 'https://subdomain.razorpay.com');

        $plan = $this->getTrashedDbEntity('schedule_task', ['type' => 'cds_pricing']);

        $this->assertNotNull($plan->deleted_at);
    }

    public function testCustomDomainPlanIdUpdate()
    {
        $plans = $this->createCDSPlan();

        $domainCreateRequest = [
            'url' => '/v1/payment_pages/cds/domains',
            'method' => 'post',
            'content' => [
                'merchant_id' => '10000000000000',
                'domain_name' => 'mydomain-123.com',
                'plan_id'     => $plans[0]->getId()
            ]
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($domainCreateRequest);

        $this->assertArrayKeysExist($response, ['id', 'domain_name', 'merchant_id', 'status']);

        $updatePlanIdRequest = [
            'url' => '/payment_pages/cds/plans/plan',
            'method' => 'patch',
            'content' => [
                CDSPlans\Constants::NEW_PLAN_ID   => $plans[1]->getId(),
                CDSPlans\Constants::OLD_PLAN_ID   => $plans[0]->getId()
            ]
        ];

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::DEBUG_NOCODE_ROUTES]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($updatePlanIdRequest);

        $this->assertArrayKeysExist($response, ['response']);

        $planForMerchant = $this->getDbLastEntity('schedule_task');

        $this->assertEquals($plans[1]->getId(), $planForMerchant->getScheduleId());
    }

    public function testCustomDomainPlanIdUpdateWhenIdNotValid()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    // -------------------- Protected methods --------------------

    protected function createCDSPlan()
    {
        $request = [
            'method'    => 'POST',
            'url'       => '/payment_pages/cds/plans',
            'content'   => [
                'plans' => [
                    [
                        'alias'    => CDSPlans\Aliases::MONTHLY_ALIAS,
                        'period'   => 'monthly',
                        'interval' => '1'
                    ],
                    [
                        'alias'    => CDSPlans\Aliases::QUARTERLY_ALIAS,
                        'period'   => 'monthly',
                        'interval' => '3',
                    ],
                    [
                        'alias'    => CDSPlans\Aliases::BIYEARLY_ALIAS,
                        'period'   => 'monthly',
                        'interval' => '6'
                    ],
                ],
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('plans', $response);

        $plans = $this->getDbEntities('schedule', [
            Schedule\Entity::TYPE => Schedule\Type::CDS_PRICING
        ]);

        $this->assertEquals(3, count($plans));

        return $plans;
    }

    protected function createCustomDomainForMerchantWithPlan(
        string $planId,
        string $merchantId = '10000000000000',
        string $domain = "mydomain-121.com"
    )
    {
        $request = [
            'url'     => '/payment_pages/cds/domains',
            'method'  => 'post',
            'content' => [
                'merchant_id' => $merchantId,
                'domain_name' => $domain,
                'plan_id'     => $planId
            ]
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayKeysExist($response, ['id', 'domain_name', 'merchant_id', 'status']);

    }

    protected function assertManipulateOrderItemAndMakePayment(
        string $exceptionMessage = "",
        int $updateAmount = 10000,
        array $attributes = [],
        string $exceptionClass = BadRequestValidationFailureException::class
    )
    {
        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $order  = $data['payment_link_order']['order'];

        $page   = $data['payment_link'];

        // edit the page to change the amount
        $repo = new PaymentLink\PaymentPageItem\Repository();

        $ppiEntity = $repo->find(self::TEST_PPI_ID);

        $ppiEntity->item->setAttribute('amount', $updateAmount);

        // now the total amount has changed
        $ppiEntity->item->save();

        $this->expectException($exceptionClass);

        $this->expectErrorMessage($exceptionMessage);

        $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $page,
            $this->getDbEntityById('order', $order->getId())
        );
    }

    protected function makeRequestToCreatePaymentHandle(string $billingLabel = 'Test Merchant')
    {
        $this->ba->proxyAuthLive($billingLabel);

        $this->mockGimliPaymentHandle();

        $request = [
            'method' => 'POST',
            'url' => '/v1/payment_handle',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function setupPartnerWebhookSettingTestCase(array $webhookSettings, bool $validUdf = true)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $this->createPaymentPageItem();
        $settings = [
            Entity::PARTNER_WEBHOOK_SETTINGS    => $webhookSettings,
            Entity::UDF_SCHEMA                  => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":0}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":[],\"settings\":{\"position\":1}},{\"name\":\"name\",\"title\":\"Name\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":3}},{\"name\":\"address\",\"title\":\"Address\",\"required\":true,\"type\":\"string\",\"options\":{\"cmp\":\"textarea\",\"is_shiprocket\":true},\"settings\":{\"position\":4}},{\"name\":\"city\",\"title\":\"City\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":5}},{\"name\":\"state\",\"title\":\"State\",\"required\":true,\"type\":\"string\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":6}},{\"name\":\"pincode\",\"title\":\"Pincode\",\"required\":true,\"type\":\"number\",\"minLength\":5,\"maxLength\":7,\"pattern\":\"number\",\"options\":{\"is_shiprocket\":true},\"settings\":{\"position\":7}}]",
        ];

        if ($validUdf === false)
        {
            $settings[Entity::UDF_SCHEMA ] = "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":6}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":7}}]";
        }

        $pl->getSettingsAccessor()->upsert($settings)->save();
    }

    /**
     * @param array $experimentMap
     *
     * @return void
     */
    protected function mockRazorxExperiments(array $experimentMap)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($experimentMap)
                {
                    $keyExists = $experimentMap[$feature] ?? false;
                    if ($keyExists)
                    {
                        return $experimentMap[$feature];
                    }

                    return 'off';
                }));
    }
    protected function createAndFetchMocks()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    protected function activateMerchantToTriggerPaymentHandleCreation(string $billingLabel = 'Test Label 123')
    {
        $this->mockGimliPaymentHandle($billingLabel);

        $this->createAndFetchMocks();

        $this->ba->proxyAuthLive();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => '10000000000000',
            'promoter_pan'      => 'EBPPK8222K',
            'promoter_pan_name' => 'User 1',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => $billingLabel]);

        // Activation request for instant activation
        $content = [
            'activation_form_milestone'   => 'L1',
            'company_cin'                 => 'U65999KA2018PTC114468',
            'business_category'           => 'ecommerce',
            'business_subcategory'        => 'fashion_and_lifestyle',
            'promoter_pan'                => 'ABCPE0000Z',
            'business_name'               => 'business_name',
            'business_dba'                => $billingLabel,
            'business_type'               => 1,
            'business_model'              => '1245',
            'business_website'            => 'https://example.com',
            'business_operation_address'  => 'My Addres is somewhere',
            'business_operation_state'    => 'KA',
            'business_operation_city'     => 'Bengaluru',
            'business_operation_pin'      => '560095',
            'business_registered_address' => 'Registered Address',
            'business_registered_state'   => 'DL',
            'business_registered_city'    => 'Delhi',
            'business_registered_pin'     => '560050',
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => $content
        ];

        $activationResponse = $this->makeRequestAndGetRawContent($request);

        $activationResponse->assertStatus(200);
    }

    protected function assertDonationGoalTrackerRefundFlow(Entity $paymentLink, array $goalTrackerSubset, array $refundInput): void
    {
        $this->refundPayment(
            $refundInput['pay_id'],
            $refundInput['amount'],
            [],
            [],
            ($refundInput['reverse_all'] ?? "1") === "1"
        );
        $pl = $this->getDbEntityById('payment_link', $paymentLink->getId());

        $this->assertDonationGoalTracker($pl, $goalTrackerSubset);
    }

    protected function createDonationGoalTrackerWithSinglePayment(
        array $paymentLinkAttribute = [],
        array $metadata = [],
        string $trackerType = PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
        string $goalIsActive = "1",
        array $orderAttribute = []
    ): array
    {
        $data = $this->createPaymentLinkAndOrderForThat($paymentLinkAttribute, $orderAttribute);
        if (empty($metadata))
        {
            $metadata =  [
                Entity::GOAL_AMOUNT             => "10000",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ];
        }
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => $trackerType,
                Entity::GOAL_IS_ACTIVE  => $goalIsActive,
                Entity::META_DATA       => $metadata,
            ]
        ];

        $pl     = $data['payment_link'];
        $order  = $data['payment_link_order']['order'];

        $pl->getSettingsAccessor()->upsert($settings)->save();

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $order);

        return [$pl, $order, $payment];
    }

    protected function assertDonationGoalTracker(Entity $paymentLink, array $goalTrackerSubArray)
    {
        $serializer     = new PaymentLink\ViewSerializer($paymentLink);
        $settings       = $serializer->serializeSettingsWithDefaults();
        $forHostedPage  = $serializer->serializeForHosted();

        $resHostedPagePayloadSubSet = [
            "payment_link"  => [
                Entity::SETTINGS => $goalTrackerSubArray
            ]
        ];
        $this->assertArraySubset($goalTrackerSubArray, $settings);
        $this->assertArraySubset($resHostedPagePayloadSubSet, $forHostedPage);
    }

    /**
     * Helper method to make payment for given payment link (with auto-capture) and do the necessary assertions
     *
     * @param  PaymentLinkModel\Entity $paymentLink
     * @return Payment\Entity
     */
    protected function makePaymentForPaymentLinkAndAssert(PaymentLinkModel\Entity $paymentLink, Order\Entity $order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount();

        $payment = $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
            Payment\Entity::AMOUNT => $order->getAmount(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentLink->getAmount(), $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        return $payment;
    }

    protected function getLastPaymentLinkEntityAndAssert(array $expected)
    {
        $paymentLink = $this->getDbLastEntity('payment_link');

        $this->assertArraySelectiveEquals($expected, $paymentLink->toArray());
    }

    protected function callViewUrlAndMakeAssertions(
        string $id = self::TEST_PL_ID,
        int $code = 200,
        string $message = null)
    {
        $response = $this->call('GET', "/v1/payment_pages/pl_{$id}/view");

        $response->assertStatus($code);

        // If there is an message expected, assert that it exists in the response content
        if (empty($message) === false)
        {
            $this->assertContains($message, $response->getContent());
        }
    }

    protected function assertLineItems(array $lineItemsInvoice, array $lineItemsPP)
    {
        $this->assertEquals(count($lineItemsInvoice), count($lineItemsPP));

        for($i = 0; $i < count($lineItemsPP); $i++)
        {
            $itemInvoice = $lineItemsInvoice[$i];
            $itemPP = $lineItemsPP[$i];
            $this->assertEquals($itemInvoice[LineItem\Entity::NAME], $itemPP[LineItem\Entity::NAME]);
            $this->assertEquals($itemInvoice[LineItem\Entity::DESCRIPTION], $itemPP[LineItem\Entity::DESCRIPTION]);
            $this->assertEquals($itemInvoice[LineItem\Entity::AMOUNT], $itemPP[LineItem\Entity::AMOUNT]);
            $this->assertEquals($itemInvoice[LineItem\Entity::CURRENCY], $itemPP[LineItem\Entity::CURRENCY]);
            $this->assertEquals($itemInvoice[LineItem\Entity::QUANTITY], $itemPP[LineItem\Entity::QUANTITY]);
        }
    }

    protected function createAndPutImageFileInRequest(string $callee, $files = [])
    {
        if (empty($files) === true)
        {
            $uploadedFile = $this->createUploadedFile(__DIR__ . '/Helpers/' . 'number2.png', 'number2.png', 'image/png');

            $this->testData[$callee]['request']['content']['images'][] = $uploadedFile;
        }
    }

    protected function createUploadedFile(string $url, $fileName = 'test.jpeg', $mime = 'image/jpeg'): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true
        );
    }

    protected function createHandleFromBillingLabel(string $billingLabel)
    {
        $handle = preg_replace('/[^a-zA-Z0-9-]+/', '', $billingLabel);

        $handle = '@' . strtolower(str_replace(' ', '', $handle));

        if(strlen($handle) > Entity::MAX_SLUG_LENGTH)
        {
            $handle = substr($handle, 0, Entity::MAX_SLUG_LENGTH);
        }

        return $handle;
    }


    protected function mockGimliPaymentHandle(string $billingLabel = 'Test Label 123')
    {
        $handle = $this->createHandleFromBillingLabel($billingLabel);

        $gimli = $this->createMock(Gimli::class);

        $gimli->method('expandAndGetMetadata')->willReturn(null);

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('driver')->willReturn($gimli);

        $elfin->method('shorten')->willReturn(
            "https://rzp.io/i/" . $handle
        );

        $gimli->method('update')->willReturn([
            "hash" => $handle
        ]);

        $this->app->instance('elfin', $elfin);
    }

    public function testCreate1CCPaymentLink()
    {
        $this->startTest();
    }

    // Test for asserting fee_in_mcc attribute when calling
    // PlinkController@fetchPaymentDetails
    public function testFetchPaymentDetails()
    {
        // test for fee_in_mcc as zero value
        //
        $payment = $this->fixtures->create('payment', ['merchant_id' => self::TEST_MID]);

        $fetchPaymentDetails = [
            'url' => '/v1/payment_links_payment/' . $payment['public_id'],
            'method' => 'get',
            'content' => []
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($fetchPaymentDetails);

        $this->assertNotNull($response['payment']);
        $this->assertEquals($response['payment']['currency'], 'INR');
        $this->assertEquals($response['payment']['merchant_id'], self::TEST_MID);
        $this->assertEquals($response['payment']['fee_in_mcc'], 0);


        // test for fee_in_mcc as non zero value and
        // no payment meta
        //
        $card = $this->fixtures->create('card', [
            'network'           =>  'Visa',
            'country'           =>  'US',
            'international'     => true,
        ]);

        // Sample payment: https://admin-dashboard.razorpay.com/admin/entity/payment/live/pay_LG3qJUh0ZeoJBw
        // Slack: https://razorpay.slack.com/archives/C7WEGELHJ/p1676352566252339?thread_ts=1675832734.858449&cid=C7WEGELHJ
        $paymentAttributes = [
            'base_amount'       => 487066,
            'amount'            => 6042,
            'currency'          => 'USD',
            'method'            => 'card',
            'email'             => 'am@rzp.io',
            'contact'           => '9876543210',
            'card_id'           => $card->getId(),
            'merchant_id'       => self::TEST_MID,
            'international'     => true,
            'fee'               => 16652,
            'tax'               => 2540,
            'mdr'               => 4384,
            'fee_bearer'        => 'customer',
        ];

        $payment = $this->fixtures->edit('payment', $payment['id'], $paymentAttributes);

        $fetchPaymentDetails = [
            'url' => '/v1/payment_links_payment/' . $payment['public_id'],
            'method' => 'get',
            'content' => []
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($fetchPaymentDetails);

        $this->assertNotNull($response['payment']);
        $this->assertEquals($response['payment']['currency'], 'USD');
        $this->assertEquals($response['payment']['merchant_id'], self::TEST_MID);
        $this->assertEquals($response['payment']['fee'], 16652);
        $this->assertEquals($response['payment']['fee_in_mcc'], 0);


        // test for fee_in_mcc as non zero value and
        // payment meta set as zero value
        //
        $metaAttributes = [
            'payment_id'        => $payment['id'],
            'mcc_forex_rate'    => '0',
            'mcc_applied'       => true,
            'mcc_mark_down_percent' => '2.5',
        ];

        $meta = $this->fixtures->create('payment_meta', $metaAttributes);

        $fetchPaymentDetails = [
            'url' => '/v1/payment_links_payment/' . $payment['public_id'],
            'method' => 'get',
            'content' => []
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($fetchPaymentDetails);

        $this->assertNotNull($response['payment']);
        $this->assertEquals($response['payment']['currency'], 'USD');
        $this->assertEquals($response['payment']['merchant_id'], self::TEST_MID);
        $this->assertEquals($response['payment']['fee'], 16652);
        $this->assertEquals($response['payment']['fee_in_mcc'], 0);


        // test for fee_in_mcc as non zero value and
        // payment meta set as non-zero value
        //
        $metaAttributes = [
            'mcc_forex_rate' => '82.609197',
        ];

        $this->fixtures->edit('payment_meta', $meta['id'], $metaAttributes);

        $fetchPaymentDetails = [
            'url' => '/v1/payment_links_payment/' . $payment['public_id'],
            'method' => 'get',
            'content' => []
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($fetchPaymentDetails);

        $this->assertNotNull($response['payment']);
        $this->assertTrue($response['payment']['international']);
        $this->assertEquals($response['payment']['currency'], 'USD');
        $this->assertEquals($response['payment']['merchant_id'], self::TEST_MID);
        $this->assertEquals($response['payment']['amount'], 6042);
        $this->assertEquals($response['payment']['fee'], 16652);
        $this->assertEquals($response['payment']['fee_in_mcc'], 202);
    }
}
