<?php

namespace Functional\QrCode;

use Mockery;
use Carbon\Carbon;
use RZP\Services\Mock;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Terminal\Type;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\LogicException;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiKotakQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->create('terminal:dedicated_upi_kotak_terminal');


        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->config['gateway.mock_upi_mozart'] = true;
    }


    public function testCreateStaticKotakQRWithoutAnyTerminal(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ]);
    }

    public function testCreateStaticKotakQrWithTerminal(): void
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on'
                                      ]);
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();
    }

    private function runQrCodeEntityAssertions(): void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $tr          = $intentParam['tr'];
        $tid         = $intentParam['tid'];
        $pa          = $intentParam['pa'];

        $this->assertStringContainsString('@kotak', $pa);
        $this->assertEquals($qrCodeEntity['reference'] . 'qrv2', $tr);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals($qrCodeEntity['reference'], $tid);
            $amount = $qrCodeEntity['amount'] / 100.00;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
        {
            $this->assertNull($tid);

            if ($qrCodeEntity['fixed_amount'] === true)
            {
                $amount = $qrCodeEntity['amount'] / 100.00;
                $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
            }
        }
    }

    public function testCreateStaticKotakQrWithFixedAmount(): void
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on'
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'payment_amount' => '300',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicKotakQrCode(): void
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicKotakQrWithoutTerminal(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ]);
    }

    public function testCreateKotakQrWithExpiry(): void
    {
        //If close_by is passed in request, QR should not be created via Kotak terminal
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on'
                                      ]);
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_KOTAK);

        $days = 3;

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60),
            ],
            'live',
            'LiveAccountMer');
    }

    //error
    public function testCreateKotakQrWithCloseQrOnDemandFlagEnabled(): void
    {
        $this->markTestSkipped('feature close_qr_on_demand is deprecated');
        //If CLOSE_QR_ON_DEMAND is enabled for merchant, QR should not be created via Kotak terminal
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on'
                                      ]);
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_KOTAK);

        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');
    }

    //error
    public function testCloseKotakQrWithCloseQrOnDemandFlagEnabled(): void
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);

//        $this->expectException(BadRequestException::class);
//
//        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');

        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');

        $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals('closed', $qrCode->getStatus());
    }

    //error
    public function testCloseKotakQrWithCloseQrOnDemandFlagDisabled(): void
    {
        $this->markTestSkipped('feature close_qr_on_demand is deprecated');
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on'
                                      ]);
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');

        $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');
    }

    public function runQrPaymentEntityAssertions($expected = true, $paymentRequestEntity = [], $upiRequestEntity = [], $mode = 'test'): void
    {
        $qrPayment        = $this->getLastEntity('qr_payment', true, $mode);
        $payment          = $this->getLastEntity('payment', true, $mode);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, $mode);
        $qrCodeEntity     = $this->getLastEntity('qr_code', true, $mode);
        $upi              = $this->getLastEntity('upi', true, $mode);
        $intentParam      = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('107611570997', $payment['reference16']);
        $this->assertNotNull($qrPayment['transaction_time']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        $this->assertEquals($paymentRequestEntity['description'], $qrPayment['notes']);
        $this->assertEquals($upi['merchant_reference'], $intentParam['tr']);
        $this->assertEquals('pullak@okhdfcbank', $upi['vpa']);
        $this->assertEquals('107611570997', $upi['npci_reference_id']);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals('closed', $qrCodeEntity['status']);
        }
        else
        {
            $this->assertEquals('active', $qrCodeEntity['status']);
        }

        if ($expected === true)
        {
            $this->assertEquals(null, $qrPaymentRequest['failure_reason']);
            $this->assertEquals(true, $qrPaymentRequest['expected']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals(true, $qrPayment['expected']);
        }
        else
        {
            $this->assertEquals(false, $qrPaymentRequest['expected']);
            $this->assertEquals('refunded', $payment['status']);
            $this->assertEquals(false, $qrPayment['expected']);
        }
    }

    public function testQrPaymentOnStaticQrCode()
    {

        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on'
                                      ]);
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnDynamicQrCode()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnIntentSubType()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');
        $upi_metadata     = $this->getLastEntity('upi_metadata', true, 'live');

        $this->assertEquals('intent', $upi_metadata['flow']);
        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnInvalidQrCode()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        // Changing the ID of the QR to make the request invalid
        $qrCodeEntity['id'] = '1000RandomQrId';

        $countOfQrPaymentRequestsBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsBefore        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsBefore          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesBefore       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $countOfQrPaymentRequestsAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsAfter        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsAfter          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesAfter       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfUpiEntitiesBefore, $countOfUpiEntitiesAfter);
        $this->assertEquals($countOfQrPaymentsBefore, $countOfQrPaymentsAfter);
        $this->assertEquals($countOfQrPaymentRequestsBefore, $countOfQrPaymentRequestsAfter);
        $this->assertEquals($countOfPaymentsBefore, $countOfPaymentsAfter);

        $this->assertFalse($response['success']);
    }

    public function testMultipleQrPaymentsOnDynamicQrCode()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiKotakPayment($qrCodeEntity, ['rrn' => '107611570998',]);

        $this->assertTrue($response['success']);

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(expected: false, mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentFailedCallback()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $countOfQrPaymentRequestsBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsBefore        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsBefore          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesBefore       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $response = $this->makeUpiKotakPayment($qrCodeEntity, ['statusCode' => '11', 'status' => 'FAILED']);

        $countOfQrPaymentRequestsAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsAfter        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsAfter          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesAfter       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfUpiEntitiesBefore, $countOfUpiEntitiesAfter);
        $this->assertEquals($countOfQrPaymentsBefore, $countOfQrPaymentsAfter);
        $this->assertEquals($countOfQrPaymentRequestsBefore + 1, $countOfQrPaymentRequestsAfter);
        $this->assertEquals($countOfPaymentsBefore, $countOfPaymentsAfter);

        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, 'live');

        $this->assertEquals(str_after($qrCodeEntity['id'], 'qr_'), $qrPaymentRequest['qr_code_id']);
        $this->assertEquals('failed callback', $qrPaymentRequest['failure_reason']);
        $this->assertArraySelectiveEquals(['source' => 'callback', 'request_from' => 'bank'], json_decode($qrPaymentRequest['request_source'], true));
        $this->assertEquals('107611570997', $qrPaymentRequest['transaction_reference']);
        $this->assertEquals(0, $qrPaymentRequest['is_created']);

        // We return success as true in this case
        $this->assertTrue($response['success']);
    }

    public function testProcessKotakQrReconInternalWithoutPayment(): void
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $request = $this->testData['testProcessKotakQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('upi_kotak', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testProcessKotakQrReconInternalWithExistingPayment(): void
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $this->makeUpiKotakPayment($qrCodeEntity);
        $existingPayment = $this->getDbLastEntity('payment', 'live');

        $request = $this->testData['testProcessKotakQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $existingPayment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('upi_kotak', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals($existingPayment['id'], $payment['id']);
    }

    public function handleUfhService($qrCodeId)
    {
        $ufhServiceMock = Mockery::mock(Mock\UfhService::class, [$this->app])->makePartial();

        $exception = new ServerErrorException('Unavailable', 'SERVER_ERROR');

        $ufhServiceMock->shouldReceive('fetchFiles')
            ->andReturnUsing(function() use (&$count, $exception, $qrCodeId) {
                if ($count == 0) {
                    ++$count;
                    throw $exception;
                }
                else {
                    ++$count;
                    return [
                        'entity'  => 'collection',
                        'count'   => 1,
                        'items'   => [
                            [
                                'id'            => 'file_10RandomFileId',
                                'type'          => $type ?? 'explanation_letter',
                                'entity_type'   => 'qr_code',
                                'entity_id'     => $qrCodeId,
                                'name'          => 'QrCode.jpg',
                                'location'      => 'random/qrcode/location',
                                'bucket'        => 'test_bucket',
                                'mime'          => 'image/jpeg',
                                'extension'     => 'jpg',
                                'merchant_id'   => '10000000000000',
                                'store'         => 's3',
                            ],
                        ],
                    ];
                }
            });

        $this->app->instance('ufh.service', $ufhServiceMock);
    }

    public function createTestOrg($displayName = 'HDFC CollectNow', $businessName = 'HDFC CollectNow')
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', [
            'display_name'            => $displayName,
            'business_name'            => $businessName,
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'org_custom_upi_logo',
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org'
        ]);

        return $org;
    }

    public function downloadQrInTestModeForKotak($qrCodeId)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/t/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();

        return $this->sendRequest($request);
    }

    public function testCreateUpiQRWithOrgLogoFeatureFlagAndHDFCOrg()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Shubham',
            'org_id' => $org->getId(),
            'logo_url' => '/img/rbllogo.png',
        ]);

        $this->fixtures->edit('org',$org->getId(),[
            'main_logo_url' => '/img/logo_black.png',
        ]);


        $terminal = $this->fixtures->terminal->createUpiKotakTerminal();

        $qrCode = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100000
            ],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->handleUfhService($qrCodeId);

        $response = $this->downloadQrInTestModeForKotak($qrCodeId);

        $contentType = $response->baseResponse->headers->get('content-type');

        $this->assertEquals('image/png', $contentType);

    }

    public function testCreateUpiQRWithOrgLogoFeatureFlagAndkotakOrg()
    {
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $org = $this->createTestOrg('kotak', 'kotak');

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Shubham',
            'org_id' => $org->getId(),
            'logo_url' => '/img/rbllogo.png',
        ]);

        $this->fixtures->edit('org',$org->getId(),[
            'main_logo_url' => '/img/logo_black.png',
        ]);


        $terminal = $this->fixtures->terminal->createUpiKotakTerminal();

        $qrCode = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100000
            ],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->handleUfhService($qrCodeId);

        $response = $this->downloadQrInTestModeForKotak($qrCodeId);

        $contentType = $response->baseResponse->headers->get('content-type');

        $this->assertEquals('image/png', $contentType);

    }

    public function testCreateUpiQRWithOrgLogoAndMerchantFeatureFlagAndkotakOrg()
    {
        $this->markTestSkipped();
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->fixtures->merchant->addFeatures(['custom_merchant_upi_qr']);

        $org = $this->createTestOrg('kotak', 'kotak');

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Shubham',
            'org_id' => $org->getId(),
            'logo_url' => '/img/rbllogo.png',
        ]);

        $this->fixtures->edit('org',$org->getId(),[
            'main_logo_url' => '/img/logo_black.png',
        ]);

        $terminal = $this->fixtures->terminal->createUpiKotakTerminal();

        $qrCode = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100000
            ],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->handleUfhService($qrCodeId);

        $response = $this->downloadQrInTestModeForKotak($qrCodeId);

        $contentType = $response->baseResponse->headers->get('content-type');

        $this->assertEquals('image/png', $contentType);

    }

    public function testCreateUpiQRWithOrgLogoAndMerchantFeatureFlagAndHDFCOrg()
    {
        $this->markTestSkipped();
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $this->fixtures->merchant->addFeatures(['custom_merchant_upi_qr']);

        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Shubham',
            'org_id' => $org->getId(),
            'logo_url' => '/img/rbllogo.png',
        ]);

        $this->fixtures->edit('org',$org->getId(),[
            'main_logo_url' => '/img/logo_black.png',
        ]);

        $terminal = $this->fixtures->terminal->createUpiKotakTerminal();

        $qrCode = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100000
            ],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->handleUfhService($qrCodeId);

        $response = $this->downloadQrInTestModeForKotak($qrCodeId);

        $contentType = $response->baseResponse->headers->get('content-type');

        $this->assertEquals('image/png', $contentType);
    }

    public function testCreateUpiQRWithOrgLogoAndOrgFeatureFlagAndkotakOrg()
    {
        $this->markTestSkipped();
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $org = $this->createTestOrg('kotak', 'kotak');

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Shubham',
            'org_id' => $org->getId(),
            'logo_url' => '/img/rbllogo.png',
        ]);

        $this->fixtures->edit('org',$org->getId(),[
            'main_logo_url' => '/img/logo_black.png',
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'custom_org_upi_qr',
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org'
        ]);

        $terminal = $this->fixtures->terminal->createUpiKotakTerminal();

        $qrCode = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100000
            ],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->handleUfhService($qrCodeId);

        $response = $this->downloadQrInTestModeForKotak($qrCodeId);

        $contentType = $response->baseResponse->headers->get('content-type');

        $this->assertEquals('image/png', $contentType);

    }

    public function testCreateUpiQRWithOrgLogoAndOrgFeatureFlagAndHDFCOrg()
    {
        $this->markTestSkipped();
        $this->setMockSplitzTreatment([
                                          'M25grFTOPZEGQS' => 'on',
            $this->config->get('app.merchant_with_qr_expiry_gt_2_hours')=> 'on',
                                      ]);
        $org = $this->createTestOrg();

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Shubham',
            'org_id' => $org->getId(),
            'logo_url' => '/img/rbllogo.png',
        ]);

        $this->fixtures->edit('org',$org->getId(),[
            'main_logo_url' => '/img/logo_black.png',
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'custom_org_upi_qr',
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org'
        ]);

        $terminal = $this->fixtures->terminal->createUpiKotakTerminal();

        $qrCode = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100000
            ],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->handleUfhService($qrCodeId);

        $response = $this->downloadQrInTestModeForKotak($qrCodeId);

        $contentType = $response->baseResponse->headers->get('content-type');

        $this->assertEquals('image/png', $contentType);
    }

    public function testProcessUnexpectedPaymentWhenMerchantIsNotLiveForOfflinePayments()
    {
        // Creating Merchant
        $this->fixtures->merchant->createAccount('LiveAccountMe1');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMe1', [
            'activated'         => true,
            'live'              => false,
            'pricing_plan_id'   => Fee::DEFAULT_PRICING_PLAN_ID
        ]);

        $this->fixtures->on('live')->merchant->addFeatures(
            [
                'qr_codes',
                'bharat_qr_v2',
                'omni_enabled'
            ],
            'LiveAccountMe1'
        );

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMe1', 'upi');
        $this->fixtures->on('live')->merchant->activate();

        // Creating Terminal
        $this->fixtures->on('live')->create('terminal:dedicated_upi_kotak_offline_terminal');

        $this->createPricingForOffline(['receiver_type' => 'qr_code']);

        $this->setMockSplitzTreatment([
            'M25grFTOPZEGQS' => 'on'
        ]);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMe1'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiKotakPayment($qrCodeEntity,
            [
                'payeevpa'      => 'testvpa1@kotak',
                'merchantcode'  => 'razorpayupi1'
            ]);

        $paymentEntity = $this->getLastEntity('payment', true, 'live');
        $qrPayment = $this->getLastEntity('qr_payment', true, 'live');

        $this->assertEquals('in_person', $paymentEntity['reference13']);
        $this->assertEquals(0, $qrPayment['expected']);
    }

    public function createPricingForOffline($contents = [])
    {
        $posQRPricingPlan = [
            'plan_id'           => '1hDYlICobzOCYt',
            'plan_name'         => 'TestMerchantPosUPIPricingPlan1',
            'payment_method'    => 'upi',
            'org_id'            => '100000razorpay',
            'type'              => 'pricing',
            'feature'           => 'payment',
            'receiver_type'     => 'offline',
            'fee_bearer'        => 'platform',
            'percent_rate'      => 0,
            'fixed_rate'        => 0,
            'channel'           => 'in_person',
        ];

        $posQRPricingPlan = array_merge($posQRPricingPlan, $contents);

        $this->fixtures->create('pricing', $posQRPricingPlan);
    }
}
