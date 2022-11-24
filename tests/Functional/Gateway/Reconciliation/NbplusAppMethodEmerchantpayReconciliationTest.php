<?php

namespace RZP\Tests\Functional\Gateway\File;

use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Services\Mock\Scrooge;
use RZP\Models\Payment\Refund;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceAppsTest;

class NbplusAppMethodEmerchantpayReconciliationTest extends NbPlusPaymentServiceAppsTest
{
    use ReconTrait;
    use FileHandlerTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusAppMethodReconciliationTestData.php';

        parent::setUp();
    }

    public function testEmerchantpayPaymentSuccessRecon()
    {
        $this->provider = 'trustly';

        $this->setConfigurationInternationalApp($this->provider);

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $paymentArray = $this->getDefaultAppPayment($this->provider);
        $paymentArray['amount'] = 1000;
        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;
        $paymentArray['_']['library'] = 'checkoutjs';
        $paymentArray['billing_address'] = $this->getBillingAddressDetails();

        $payment = $this->makePaymentViaAjaxRouteAndCapture($paymentArray);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data[] = $this->testData[__FUNCTION__];

        $data[0]['Merchant Transaction Id'] = $payment['id'];

        $payment_meta = $this->getDbLastEntityToArray(Entity::PAYMENT_META);
        $data[0]['Amount'] = $payment_meta['gateway_amount'];

        $file = $this->writeToExcelFile($data, 'Sale Approved', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'Sale Approved.xlsx');

        $this->reconcile($uploadedFile, Base::EMERCHANTPAY);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['acquirer_data']['amount'], $payment[Payment::AMOUNT] / 100);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testEmerchantpayRefundSuccessRecon()
    {
        $this->provider = 'trustly';

        $this->setConfigurationInternationalApp($this->provider);

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $paymentArray = $this->getDefaultAppPayment($this->provider);
        $paymentArray['amount'] = 1000;
        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;
        $paymentArray['_']['library'] = 'checkoutjs';
        $paymentArray['billing_address'] = $this->getBillingAddressDetails();

        $payment = $this->makePaymentViaAjaxRouteAndCapture($paymentArray);

        $this->refundPayment($payment['public_id'], $payment['amount']);

        $refund = $this->getDbLastEntity('refund');

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $this->assertEquals(1, $refund[Refund\Entity::IS_SCROOGE]);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], 'refunded');

        $data[] = $this->testData[__FUNCTION__];

        $data[0]['Merchant Transaction Id'] = $refund['id'];
        $payment_meta = $this->getDbLastEntityToArray(Entity::PAYMENT_META);
        $data[0]['Amount'] = $payment_meta['gateway_amount'];

        $file = $this->writeToExcelFile($data, 'Refund Approved', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'Refund Approved.xlsx');

        $this->mockScroogeResponse($refund['id'], $payment['id']);

        $this->reconcile($uploadedFile, Base::EMERCHANTPAY);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['acquirer_data']['amount'], $payment[Payment::AMOUNT] / 100);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testEmerchantpayPaymentReconAmountMismatch()
    {
        $this->provider = 'trustly';

        $this->setConfigurationInternationalApp($this->provider);

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $paymentArray = $this->getDefaultAppPayment($this->provider);
        $paymentArray['amount'] = 1000;
        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;
        $paymentArray['_']['library'] = 'checkoutjs';
        $paymentArray['billing_address'] = $this->getBillingAddressDetails();

        $payment = $this->makePaymentViaAjaxRouteAndCapture($paymentArray);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data[] = $this->testData['testEmerchantpayPaymentSuccessRecon'];

        $data[0]['Merchant Transaction Id'] = $payment['id'];
        $data[0]['Transaction Amount'] = '45211';

        $file = $this->writeToExcelFile($data, 'Sale Approved', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'Sale Approved.xlsx');

        $this->reconcile($uploadedFile, Base::EMERCHANTPAY);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'partially_processed');
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    private function getDefaultPaymentFlowsRequestData()
    {
        $flowsData = [
            'content' => ['amount' => 1000, 'currency' => 'INR', 'provider' => 'trustly'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }

    public function mockScroogeResponse($refundId, $paymentId)
    {
        $scroogeResponse = [
            'body' => [
                'data' => [
                    ltrim($refundId, '0') => [
                        'payment_id'     => PublicEntity::stripDefaultSign($paymentId),
                        'refund_id'      => PublicEntity::stripDefaultSign($refundId)
                    ],
                ]
            ]
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getRefundsFromPaymentIdAndGatewayId'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefundsFromPaymentIdAndGatewayId')->willReturn($scroogeResponse);

    }

    private function setConfigurationInternationalApp($provider = 'trustly'){

        $this->terminal = $this->fixtures->create('terminal:emerchantpay_terminal');

        $this->provider = $provider;

        $this->fixtures->merchant->enableApp('10000000000000', $provider);

        $this->fixtures->merchant->addFeatures(['address_name_required']);

        $this->fixtures->merchant->edit('10000000000000');

        $this->ba->privateAuth();
    }

    private function makePaymentViaAjaxRouteAndCapture($paymentArray){

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->capturePayment(
            "pay_".$payment['id'],
            $payment['amount'],$payment['currency']);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        return $payment;
    }

    private function getBillingAddressDetails(){
        $billing_address['first_name'] = "Max";
        $billing_address['last_name']  = "Musterman";
        $billing_address['line1'] = "91,Apartment 7R";
        $billing_address['line2'] = "Wellington Street";
        $billing_address['city'] = "Striya";
        $billing_address['state'] = "Tauchen";
        $billing_address['country'] = "at";
        $billing_address['postal_code'] = "202112";

        return $billing_address;
    }

}
