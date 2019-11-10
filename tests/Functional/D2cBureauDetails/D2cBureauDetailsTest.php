<?php

namespace RZP\Tests\Functional\D2cBureauDetails;

use RZP\Services\Mock\Mozart;
use RZP\Models\D2cBureauDetail;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class D2cBureauDetailsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/D2cBureauDetailsTestData.php';

        parent::setUp();

        $this->merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $this->user = $this->fixtures->user->createUserForMerchant($this->merchantDetail['merchant_id'], [
            'name'              => 'john doe',
            'contact_mobile'    => '9876543210',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::SHOW_CREDIT_SCORE,
            'entity_id'     => $this->merchantDetail['merchant_id'],
            'entity_type'   => 'merchant',
        ]);
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
            'pan'               => 'ABCDE1234F',
//                'created_at'        => 1571374473
        ], $d2cOwnerDetails);
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

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $response['id'],]);

        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->will($this->returnCallback(
                                function ($namespace, $gateway, $action, $input, $version, $useMozartMappedInternalErrorCode)
                                {
                                    $this->assertArraySelectiveEquals([
                                            'first_name'    => 'testhello',
                                            'address'       => 'Adress',
                                            'city'          => 'city',
                                    ], $input['d2c_bureau_details']);

                                    return [
                                        'success'   => true,
                                        'data'      => [
                                            'score'         => '752',
                                            'report'        => [
                                                'active_accounts'                           => '1',
                                                'closed_accounts'                           => '1',
                                                'count_of_accounts'                         => '2',
                                                'secured_account_outstanding_balance'       => '152000',
                                                'total_outstanding_balance'                 => '152000',
                                                'un_secured_account_outstanding_balance'    => '0'
                                            ],
                                            '_raw'          => 'garbage',
                                            'raw_report'    => [
                                                'INProfileResponse' => [
                                                    'CAIS_Account'  => [
                                                        'CAIS_Account_DETAILS' => [
                                                            'AccountHoldertypeCode' => '1',
                                                            'Account_Number'        => 'XXXXXXXX0304',
                                                            'Account_Status'        => '11',
                                                            'Account_Type'          => '51',
                                                            'Amount_Past_Due'       => '501',
                                                            'CAIS_Account_History'  => [
                                                                'Asset_Classification'  => '?',
                                                                'Days_Past_Due'         => '14',
                                                                'Month'                 => '08',
                                                                'Year'                  => '2019'
                                                            ],
                                                        ],
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ];
                                }));

        $this->app->instance('mozart', $mozartServiceMock);

        $this->startTest();

        $d2cBureauReport = $this->getLastEntity('d2c_bureau_report', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => $this->merchantDetail['merchant_id'],
            'user_id'               => $this->user->getId(),
            'd2c_bureau_detail_id'  => D2cBureauDetail\Entity::verifyIdAndStripSign($response['id']),
            'provider'              => 'experian',
            'score'                 => 752,
            'report'                => '{"active_accounts": "1", "closed_accounts": "1", "count_of_accounts": "2", "total_outstanding_balance": "152000", "secured_account_outstanding_balance": "152000", "un_secured_account_outstanding_balance": "0"}',
            'ufh_file_id'           => 'rzp_file_mock_id_1000000_bureau_report',
//                'created_at'        => 1571374473
        ], $d2cBureauReport);
    }
}
