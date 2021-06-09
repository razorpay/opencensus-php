<?php

namespace RZP\Tests\Functional\D2cBureauDetails;

use Mail;
use Queue;
use Config;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Services\Mozart;
use RZP\Models\Base\Entity;
use RZP\Services\UfhService;
use RZP\Models\D2cBureauDetail;
use RZP\Jobs\D2cCsvReportCreate;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\D2CReport\D2cReportGenerated;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class D2cBureauDetailsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $reportUrl;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/D2cBureauDetailsTestData.php';

        parent::setUp();

        $this->merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id' => '10000000000000'
        ]);

        $this->user = $this->fixtures->user->createUserForMerchant($this->merchantDetail['merchant_id'], [
            'id'               => '20000000000000',
            'name'              => 'john doe',
            'contact_mobile'    => '9876543210',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::SHOW_CREDIT_SCORE,
            'entity_id'     => $this->merchantDetail['merchant_id'],
            'entity_type'   => 'merchant',
        ]);

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendMozartRequest'])
            ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
            ->will($this->returnCallback(
                function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                {
                    $this->assertArraySelectiveEquals([
                        'first_name'    => 'john',
                        'address'       => 'Adress',
                        'city'          => 'city',
                    ], $input['d2c_bureau_details']);

                    return [
                        'success'   => true,
                        'data'      => [
                            'score'         => '752',
                            'ntc_score'     => null,
                            'report'        => [
                                'active_accounts'                           => '1',
                                'closed_accounts'                           => '1',
                                'count_of_accounts'                         => '2',
                                'secured_account_outstanding_balance'       => '152000',
                                'total_outstanding_balance'                 => '152000',
                                'un_secured_account_outstanding_balance'    => '0'
                            ],
                            '_raw'          => 'garbage',
                            'raw_report'    => json_decode(file_get_contents(__DIR__ . '/helpers/report.txt'), true),
                        ]
                    ];
                }));

        $this->app->instance('mozart', $this->mozartServiceMock);

    }

    public function testPostCreate()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->startTest();

        $d2cOwnerDetails = $this->getLastEntity('d2c_bureau_detail', true);

        $this->assertArraySelectiveEquals([
//                'id'                => 'd2cbd_DVPO2EfMdU2inS',
            'first_name'        => 'testhello',
            'contact_mobile'    => '9876543210',
//                'email'             => 'tabitha.damore@mraz.biz',
            'address'           => 'Adress',
            'city'              => 'city',
            'pincode'           => '123455',
            'pan'               => 'ABCPE1234F',
//                'created_at'        => 1571374473
        ], $d2cOwnerDetails);
    }

    public function testPostCreateInternal()
    {
        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->startTest();

        $d2cOwnerDetails = $this->getLastEntity('d2c_bureau_detail', true);

        $this->assertArraySelectiveEquals([
//            'id'              => 'd2cbd_EeKAdZlPeSM4mM',
            'first_name'      => 'john',
            'last_name'       => 'doe',
            'date_of_birth'   => '1996-10-10',
            'gender'          => 'male',
            'contact_mobile'  => '9999999999',
            'email'           => 'test@razorpay.com',
            'address'         => 'Adress',
            'city'            => 'city',
            'state'           => 'PB',
            'pincode'         => '560030',
            'pan'             => 'ABCPE1234F',
//            'created_at'      => 1586858252
        ], $d2cOwnerDetails);
    }

    public function testReportDelete()
    {
        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $response = $this->startTest($this->testData['testPostCreateInternal']);

        $this->testData['testReportDelete']['request']['url'] .= $response['id'];

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPostCreateInternalWithExperianFailure()
    {
        $mozartMockCopy = $this->mozartServiceMock;

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendMozartRequest'])
                                        ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
             ->will($this->returnCallback(
                function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                {
                    $this->assertArraySelectiveEquals([
                        'first_name'    => 'john',
                        'address'       => 'Adress',
                        'city'          => 'city',
                    ], $input['d2c_bureau_details']);

                    throw new GatewayErrorException(ErrorCode::BAD_REQUEST_D2C_CREDIT_BUREAU_NO_RECORDS_FOUND);
                }));

        $this->app->instance('mozart', $this->mozartServiceMock);

        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->startTest();

        $d2cOwnerDetails = $this->getLastEntity('d2c_bureau_detail', true);

        $this->assertArraySelectiveEquals([
//            'id'              => 'd2cbd_EeKAdZlPeSM4mM',
            'first_name'      => 'john',
            'last_name'       => 'doe',
            'date_of_birth'   => '1996-10-10',
            'gender'          => 'male',
            'contact_mobile'  => '9999999999',
            'email'           => 'test@razorpay.com',
            'address'         => 'Adress',
            'city'            => 'city',
            'state'           => 'PB',
            'pincode'         => '560030',
            'pan'             => 'ABCPE1234F',
//            'created_at'      => 1586858252
        ], $d2cOwnerDetails);

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
            'error_code'        => 'BAD_REQUEST_D2C_CREDIT_BUREAU_NO_RECORDS_FOUND',
            'provider'          => 'experian',
        ], $d2cBureauReport);

        $this->mozartServiceMock = $mozartMockCopy;
    }

    public function testFetchBureauReportWithInvalidContactFailure()
    {
        $mozartMockCopy = $this->mozartServiceMock;

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendMozartRequest'])
                                        ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
                                ->will($this->returnCallback(
                                    function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                                    {
                                        $this->assertArraySelectiveEquals([
                                                                              'first_name'    => 'john',
                                                                              'address'       => 'Adress',
                                                                              'city'          => 'city',
                                                                          ], $input['d2c_bureau_details']);

                                        throw new GatewayErrorException(
                                            'BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT',
                                            'Email Vali',
                                            'Email Validation Failed or phone Validation Failed',
                                            [
                                                'error' => [
                                                    'description'               => 'Email Validation Failed or phone Validation Failed',
                                                    'gateway_error_code'        => 'Email Vali',
                                                    'gateway_error_description' => 'Email Validation Failed or phone Validation Failed',
                                                    'gateway_status_code'       => 200,
                                                    'internal_error_code'       => 'BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT'
                                                ],
                                                'data'  => [
                                                    'error' => 'Email Validation Failed or phone Validation Failed. Please try to invoke CRQ externally. Try using emailId gXXXXXXXXXa@rXXXXXXX.com and mobile number 99XXXXX243',
                                                    'raw_report' => null
                                                ]
                                            ],
                                            null,
                                            'http://api.razorpay.com/v1/los/d2c_bureau_details/');
                                    }));

        $this->app->instance('mozart', $this->mozartServiceMock);

        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->startTest();

        $d2cOwnerDetails = $this->getLastEntity('d2c_bureau_detail', true);

        $this->assertArraySelectiveEquals([
//            'id'              => 'd2cbd_EeKAdZlPeSM4mM',
            'first_name'      => 'john',
            'last_name'       => 'doe',
            'date_of_birth'   => '1996-10-10',
            'gender'          => 'male',
            'contact_mobile'  => '9999999999',
            'email'           => 'test@razorpay.com',
            'address'         => 'Adress',
            'city'            => 'city',
            'state'           => 'PB',
            'pincode'         => '560030',
            'pan'             => 'ABCPE1234F',
//            'created_at'      => 1586858252
                                          ], $d2cOwnerDetails);

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
                                              'error_code'        => 'BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT',
                                              'provider'          => 'experian',
                                          ], $d2cBureauReport);

        $this->mozartServiceMock = $mozartMockCopy;
    }

    public function testFetchBureauReportWithInvalidContactFailureNoPhoneNumber()
    {
        $mozartMockCopy = $this->mozartServiceMock;

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendMozartRequest'])
                                        ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
                                ->will($this->returnCallback(
                                    function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                                    {
                                        $this->assertArraySelectiveEquals([
                                                                              'first_name'    => 'john',
                                                                              'address'       => 'Adress',
                                                                              'city'          => 'city',
                                                                          ], $input['d2c_bureau_details']);

                                        throw new GatewayErrorException(
                                            'BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT',
                                            'Email Vali',
                                            'Email Validation Failed or phone Validation Failed',
                                            [
                                                'error' => [
                                                    'description'               => 'Email Validation Failed or phone Validation Failed',
                                                    'gateway_error_code'        => 'Email Vali',
                                                    'gateway_error_description' => 'Email Validation Failed or phone Validation Failed',
                                                    'gateway_status_code'       => 200,
                                                    'internal_error_code'       => 'BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT'
                                                ],
                                                'data'  => [
                                                    'error' => 'Email Validation Failed or phone Validation Failed. Please try to invoke CRQ externally.',
                                                    'raw_report' => null
                                                ]
                                            ],
                                            null,
                                            'http://api.razorpay.com/v1/los/d2c_bureau_details/');
                                    }));

        $this->app->instance('mozart', $this->mozartServiceMock);

        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->startTest();

        $d2cOwnerDetails = $this->getLastEntity('d2c_bureau_detail', true);

        $this->assertArraySelectiveEquals([
//            'id'              => 'd2cbd_EeKAdZlPeSM4mM',
            'first_name'      => 'john',
            'last_name'       => 'doe',
            'date_of_birth'   => '1996-10-10',
            'gender'          => 'male',
            'contact_mobile'  => '9999999999',
            'email'           => 'test@razorpay.com',
            'address'         => 'Adress',
            'city'            => 'city',
            'state'           => 'PB',
            'pincode'         => '560030',
            'pan'             => 'ABCDE1234F',
//            'created_at'      => 1586858252
                                          ], $d2cOwnerDetails);

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
                                              'error_code'        => 'BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT',
                                              'provider'          => 'experian',
                                          ], $d2cBureauReport);

        $this->mozartServiceMock = $mozartMockCopy;
    }

    public function testNtcFlow()
    {
        $mozartMockCopy = $this->mozartServiceMock;

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendMozartRequest'])
                                        ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
             ->will($this->returnCallback(
                function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                {
                    $this->assertArraySelectiveEquals([
                        'first_name'    => 'john',
                        'address'       => 'Adress',
                        'city'          => 'city',
                    ], $input['d2c_bureau_details']);

                    return [
                        'success'   => true,
                        'data'      => [
                            'score'         => null,
                            'ntc_score'     => '4',
                            'report'        => null,
                            '_raw'          => 'garbage',
                            'raw_report'    => null,
                        ]
                    ];
                }));

        $this->app->instance('mozart', $this->mozartServiceMock);

        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->reportUrl = 'report_experian_' . $bureauDetailsId . '.txt.txt';

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $bureauDetailsId,]);

        Queue::fake();

        $this->startTest();

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'            => $this->merchantDetail['merchant_id'],
            'user_id'                => $this->user->getId(),
            'd2c_bureau_detail_id'   => D2cBureauDetail\Entity::verifyIdAndStripSign($response['id']),
            'provider'               => 'experian',
            'ufh_file_id'            => null,
            'csv_report_ufh_file_id' => null,
            'error_code'             => null,
//                'created_at'        => 1571374473
        ], $d2cBureauReport);

        Queue::assertPushed(D2cCsvReportCreate::class);
    }

    public function testPatchBureauDetails()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $this->testData[__FUNCTION__]['request']['url'] .= $response['id'];

        $this->startTest();
    }


    public function testSubmitOtp()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->reportUrl = 'report_experian_' . $bureauDetailsId . '.txt.txt';

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $bureauDetailsId,]);

        $ufhServiceMock = $this->getMockBuilder(UfhService::class)
                               ->setConstructorArgs([$this->app])
                               ->setMethods(['getSignedUrl', 'uploadFileAndGetUrl'])
                               ->getMock();

        $ufhServiceMock->expects($this->at(0))
                       ->method('getSignedUrl')
                       ->will($this->returnCallback(
                           function (string $fileId, array $params = [], $merchantId = null)
                           {
                               return [
                                   'signed_url'    => 'storage/files/filestore/' .  $this->reportUrl,
                               ];
                           }));

        $ufhServiceMock->method('getSignedUrl')
                       ->will($this->returnCallback(
                           function (string $fileId, array $params = [], $merchantId = null)
                           {
                               return [
                                   'signed_url'    => 'rzp_file_mock_id_1000000_bureau_report_csv'
                               ];
                           }));

        $ufhServiceMock->method('uploadFileAndGetUrl')
                       ->will($this->returnCallback(
                           function ($file, $storageFileName, string $type, Entity $entity, array $metadata = [])
                           {
                               return [
                                   'file_id'           => 'file_1cXSLlUU8V9sXl',
                                   'relative_location' => $storageFileName,
                               ];
                           }));

        $this->app->instance('ufh.service', $ufhServiceMock);

        Queue::fake();

        $this->startTest();

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => $this->merchantDetail['merchant_id'],
            'user_id'               => $this->user->getId(),
            'd2c_bureau_detail_id'  => D2cBureauDetail\Entity::verifyIdAndStripSign($response['id']),
            'provider'              => 'experian',
//            'score'                 => 752,
//            'report'                => '{"active_accounts": "1", "closed_accounts": "1", "count_of_accounts": "2", "total_outstanding_balance": "152000", "secured_account_outstanding_balance": "152000", "un_secured_account_outstanding_balance": "0"}',
            'ufh_file_id'           => 'file_1cXSLlUU8V9sXl',
//                'created_at'        => 1571374473
        ], $d2cBureauReport);

        Queue::assertPushed(D2cCsvReportCreate::class);
    }

    public function testPatchBureauReport()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData['testSubmitOtp']['request']['url'] = strtr($this->testData['testSubmitOtp']['request']['url'], ['{id}' => $bureauDetailsId,]);

        $response = $this->makeRequestAndGetContent($this->testData['testSubmitOtp']['request']);

        $this->testData[__FUNCTION__]['request']['url'] .= $response['id'];

        $this->startTest();

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
            'interested'        => true,
        ], $d2cBureauReport);
    }

    public function testFetchBureauReportWithInternalAuth()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData['testSubmitOtp']['request']['url'] = strtr($this->testData['testSubmitOtp']['request']['url'], ['{id}' => $bureauDetailsId,]);

        $response = $this->makeRequestAndGetContent($this->testData['testSubmitOtp']['request']);

        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $response['id'],]);

        $this->startTest();
    }

    public function testFetchBureauReportWithLowerCasePanInternalAuth()
    {
        $this->fixtures->on(Mode::TEST)->create('d2c_bureau_detail',[
            'id'                          => 'aqyutshquailsq',
            'merchant_id'                 => '10000000000000',
            'user_id'                     => 'qjakcliequield',
            'first_name'                  => 'srikant',
            'last_name'                   => 'tiwari',
            'pan'                         => 'Arhpp7770l',
            'status'                      => 'verified',
        ]);

        $this->fixtures->on(Mode::TEST)->create('d2c_bureau_report',[
            'merchant_id'                 => '10000000000000',
            'user_id'                     => 'qjakcliequield',
            'd2c_bureau_detail_id'        => 'aqyutshquailsq',
            'provider'                    => 'EXPERIAN',
            'score'                       => 752
        ]);

        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->startTest();
    }

    public function testFetchBureauReportWithInternalAuthNtc()
    {
        $mozartMockCopy = $this->mozartServiceMock;

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendMozartRequest'])
                                        ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
             ->will($this->returnCallback(
                function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                {
                    $this->assertArraySelectiveEquals([
                        'first_name'    => 'john',
                        'address'       => 'Adress',
                        'city'          => 'city',
                    ], $input['d2c_bureau_details']);

                    return [
                        'success'   => true,
                        'data'      => [
                            'score'         => null,
                            'ntc_score'     => '4',
                            'report'        => null,
                            '_raw'          => 'garbage',
                            'raw_report'    => null,
                        ]
                    ];
                }));

        $this->app->instance('mozart', $this->mozartServiceMock);

        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData['testSubmitOtp']['request']['url'] = strtr($this->testData['testSubmitOtp']['request']['url'], ['{id}' => $bureauDetailsId,]);

        $response = $this->makeRequestAndGetContent($this->testData['testSubmitOtp']['request']);

        $this->ba->appAuth('rzp_test', Config::get('applications.los')['secret']);

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $response['id'],]);

        $this->startTest();
    }

    public function testGetDownloadUrl()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->reportUrl = 'report_experian_' . $bureauDetailsId . '.txt.txt';

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData['testSubmitOtp']['request']['url'] = strtr($this->testData['testSubmitOtp']['request']['url'], ['{id}' => $response['id'],]);

        $response = $this->makeRequestAndGetContent($this->testData['testSubmitOtp']['request']);

        $ufhServiceMock = $this->getMockBuilder(UfhService::class)
                               ->setConstructorArgs([$this->app])
                               ->setMethods(['getSignedUrl', 'uploadFileAndGetUrl'])
                               ->getMock();

        $ufhServiceMock->expects($this->at(0))
                       ->method('getSignedUrl')
                       ->will($this->returnCallback(
                           function (string $fileId, array $params = [], $merchantId = null)
                           {
                               return [
                                   'signed_url'    => 'storage/files/filestore/' .  $this->reportUrl,
                               ];
                           }));

        $ufhServiceMock->method('getSignedUrl')
                       ->will($this->returnCallback(
                           function (string $fileId, array $params = [], $merchantId = null)
                           {
                               return [
                                   'signed_url'    => 'rzp_file_mock_id_1000000_bureau_report_csv'
                               ];
                           }));

        $ufhServiceMock->method('uploadFileAndGetUrl')
                       ->will($this->returnCallback(
                           function ($file, $storageFileName, string $type, Entity $entity, array $metadata = [])
                           {
                               return [
                                   'file_id'           => 'rzp_file_mock_id_1000000' . '_'.$type,
                                   'relative_location' => $storageFileName,
                               ];
                           }));

        $this->app->instance('ufh.service', $ufhServiceMock);

        Mail::fake();

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $response['id'],]);

        $this->ba->adminAuth();

        $this->startTest();

        Mail::assertQueued(D2cReportGenerated::class);
    }

    public function testGetDownloadUrlForNtc()
    {
        $mozartMockCopy = $this->mozartServiceMock;

        $this->mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                        ->setConstructorArgs([$this->app])
                                        ->setMethods(['sendMozartRequest'])
                                        ->getMock();

        $this->mozartServiceMock->method('sendMozartRequest')
             ->will($this->returnCallback(
                function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                {
                    $this->assertArraySelectiveEquals([
                        'first_name'    => 'john',
                        'address'       => 'Adress',
                        'city'          => 'city',
                    ], $input['d2c_bureau_details']);

                    return [
                        'success'   => true,
                        'data'      => [
                            'score'         => null,
                            'ntc_score'     => '4',
                            'report'        => null,
                            '_raw'          => 'garbage',
                            'raw_report'    => null,
                        ]
                    ];
                }));

        $this->app->instance('mozart', $this->mozartServiceMock);

        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $response = $this->makeRequestAndGetContent($this->testData['testPostCreate']['request']);

        $bureauDetailsId = $response['id'];

        $this->reportUrl = 'report_experian_' . $bureauDetailsId . '.txt.txt';

        $this->testData['testPatchBureauDetails']['request']['url'] .= $bureauDetailsId;

        $response = $this->makeRequestAndGetContent($this->testData['testPatchBureauDetails']['request']);

        $this->testData['testSubmitOtp']['request']['url'] = strtr($this->testData['testSubmitOtp']['request']['url'], ['{id}' => $response['id'],]);

        $response = $this->makeRequestAndGetContent($this->testData['testSubmitOtp']['request']);

        Mail::fake();

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $response['id'],]);

        $this->ba->adminAuth();

        $this->startTest();

        Mail::assertNotQueued(D2cReportGenerated::class);
    }
}
