<?php

namespace RZP\Tests\Functional\Refund;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Services\RazorXClient;
use RZP\Services\Scrooge;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TransactionTrackerTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */

    protected $sharedTerminal;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TransactionTrackerTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->ba->privateAuth();
    }

    public function testCreateOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function createFailedPayment($order)
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                throw new Exception\GatewayErrorException('GATEWAY_ERROR_UNKNOWN_ERROR');
            }

            if ($action === 'inquiry')
            {
                $content['RESPCODE'] = '0';
                $content['RESPMSG'] = 'Transaction succeeded';
                $content['STATUS'] = 'TXN_SUCCESS';
            }

            return $content;
        });

        $this->doAuthPaymentAndCatchException($order);

        $payment = $this->getLastEntity('payment', true);

        return $payment;
    }

    protected function doAuthPaymentAndCatchException($order)
    {
        $this->makeRequestAndCatchException(function() use ($order)
        {
            $payment             = $this->getDefaultPaymentArray();
            $payment['amount']   = $order['amount'];
            $payment['order_id'] = $order['id'];

            $content = $this->doAuthPayment($payment);

            return $content;
        });
    }

    public function testUpiPayment()
    {
        $this->gateway = Gateway::UPI_MINDGATE;

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);

        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertNotNull($upiEntity['npci_reference_id']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);
        $this->assertNotNull($payment['acquirer_data']['upi_transaction_id']);

        $this->assertEquals($payment['reference16'], $upiEntity['npci_reference_id']);
        $this->assertNotNull($upiEntity['gateway_payment_id']);
        $this->assertEquals($payment['reference1'],$upiEntity['gateway_payment_id']);
        $this->assertSame('00', $upiEntity['status_code']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    public function assertPaymentResponse($callee, $rzpPayment, $idType)
    {
        $response = $this->runRequestResponseFlow($this->testData[$callee]);

        $this->assertEquals($rzpPayment['id'], $response['payments'][0]['payment']['id']);
        $this->assertEquals($rzpPayment['created_at'], $response['payments'][0]['payment']['created_at']);
        $this->assertEquals($rzpPayment['secondary_message'], $response['payments'][0]['payment']['secondary_message']);
        $this->assertEquals($idType, $response['id_type']);
    }

    public function assertPaymentResponses($callee, $rzpPayment, $order, $merchantTransactionId)
    {
        // Testing with RZP Payment Id
        $this->testData[$callee]['request']['content']['payment_id'] = $rzpPayment['id'];

        $this->ba->directAuth();
        $this->assertEquals($rzpPayment['id'], $this->testData[$callee]['request']['content']['payment_id']);
        $this->assertPaymentResponse($callee, $rzpPayment, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['payment_id']);

        // Testing with Payment Id
        $this->testData[$callee]['request']['content']['id'] = PublicEntity::stripDefaultSign($rzpPayment['id']);

        $this->assertEquals(PublicEntity::stripDefaultSign($rzpPayment['id']), $this->testData[$callee]['request']['content']['id']);
        $this->assertPaymentResponse($callee, $rzpPayment, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with RZP Order Id
        $this->testData[$callee]['request']['content']['order_id'] = $order['id'];

        $this->assertEquals($order['id'], $this->testData[$callee]['request']['content']['order_id']);
        $this->assertPaymentResponse($callee, $rzpPayment, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['order_id']);

        // Testing with Order Id
        $this->testData[$callee]['request']['content']['id'] = PublicEntity::stripDefaultSign($order['id']);

        $this->assertEquals(PublicEntity::stripDefaultSign($order['id']), $this->testData[$callee]['request']['content']['id']);
        $this->assertPaymentResponse($callee, $rzpPayment, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with Merchant Transaction Id in Payment Notes
        $this->testData[$callee]['request']['content']['id'] = $merchantTransactionId;

        $this->assertEquals($merchantTransactionId, $this->testData[$callee]['request']['content']['id']);
        $this->assertPaymentResponse($callee, $rzpPayment, 'merchant_reference');

        unset($this->testData[$callee]['request']['content']['id']);

        // just resetting
        $this->ba->adminAuth('test');
    }

    public function assertRefundResponse($callee, $rzpPayment, $refunds, $days, $idType)
    {
        $response = $this->runRequestResponseFlow($this->testData[$callee]);

        $this->assertEquals($days, $response['payments'][0]['refunds'][0]['days']);
        $this->assertEquals($rzpPayment['id'], $response['payments'][0]['payment']['id']);
        $this->assertEquals($refunds[0]['id'], $response['payments'][0]['refunds'][0]['id']);
        $this->assertEquals($rzpPayment['created_at'], $response['payments'][0]['payment']['created_at']);
        $this->assertEquals($refunds[0]['created_at'], $response['payments'][0]['refunds'][0]['created_at']);
        $this->assertEquals($refunds[0]['secondary_message'], $response['payments'][0]['refunds'][0]['secondary_message']);
        $this->assertEquals($idType, $response['id_type']);
    }

    public function assertResponseNotFound($callee)
    {
        $response = $this->runRequestResponseFlow($this->testData[$callee]);

        $this->assertEquals([], $response['payments']);
        $this->assertEquals('unknown', $response['id_type']);
    }

    public function assertRefundResponses($callee, $rzpPayment, $order, $refunds, $merchantTransactionId)
    {
        $createdAtDate = Carbon::createFromTimestamp($refunds[0]['created_at'], Timezone::IST);
        $expectedDate = Holidays::getNthWorkingDayFrom($createdAtDate, 7, true);
        $currentDate = Carbon::now(Timezone::IST);

        $days = $expectedDate->diffInDays($currentDate, false);

        // Testing with RZP Payment Id
        $this->testData[$callee]['request']['content']['payment_id'] = $rzpPayment['id'];

        $this->ba->directAuth();

        $this->assertEquals($rzpPayment['id'], $this->testData[$callee]['request']['content']['payment_id']);

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['payment_id']);

        // Testing with Payment Id
        $this->testData[$callee]['request']['content']['id'] = PublicEntity::stripDefaultSign($rzpPayment['id']);

        $this->assertEquals(PublicEntity::stripDefaultSign($rzpPayment['id']), $this->testData[$callee]['request']['content']['id']);

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with RZP Order Id
        $this->testData[$callee]['request']['content']['order_id'] = $order['id'];

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'rzp_id');

        $this->assertEquals($order['id'], $this->testData[$callee]['request']['content']['order_id']);


        unset($this->testData[$callee]['request']['content']['order_id']);

        // Testing with Order Id
        $this->testData[$callee]['request']['content']['id'] = PublicEntity::stripDefaultSign($order['id']);

        $this->assertEquals(PublicEntity::stripDefaultSign($order['id']), $this->testData[$callee]['request']['content']['id']);

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with RZP Refund Id
        $this->testData[$callee]['request']['content']['refund_id'] = $refunds[0]['id'];

        $this->assertEquals($refunds[0]['id'], $this->testData[$callee]['request']['content']['refund_id']);

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['refund_id']);

        // Testing with Refund Id
        $this->testData[$callee]['request']['content']['id'] = PublicEntity::stripDefaultSign($refunds[0]['id']);

        $this->assertEquals(PublicEntity::stripDefaultSign($refunds[0]['id']), $this->testData[$callee]['request']['content']['id']);

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'rzp_id');

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with Merchant Transaction Id
        $this->testData[$callee]['request']['content']['id'] = $merchantTransactionId;

        $this->assertEquals($merchantTransactionId, $this->testData[$callee]['request']['content']['id']);

        $this->assertRefundResponse($callee, $rzpPayment, $refunds, $days, 'merchant_reference');

        unset($this->testData[$callee]['request']['content']['id']);

        // just resetting
        $this->ba->adminAuth('test');
    }

    public function assertResponsesNotFound($callee, $id)
    {
        // Testing with RZP Payment Id
        $this->testData[$callee]['request']['content']['payment_id'] = 'pay_' . $id;

        $this->ba->directAuth();

        $this->assertEquals('pay_' . $id, $this->testData[$callee]['request']['content']['payment_id']);

        $this->assertResponseNotFound($callee);

        unset($this->testData[$callee]['request']['content']['payment_id']);

        $this->setUpEsMockForRefundNotesNotFound();

        // Testing with Payment Id
        $this->testData[$callee]['request']['content']['id'] = $id;

        $this->assertEquals($id, $this->testData[$callee]['request']['content']['id']);

        $this->assertResponseNotFound($callee);

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with RZP Order Id
        $this->testData[$callee]['request']['content']['order_id'] = 'order_' . $id;

        $this->assertResponseNotFound($callee);

        $this->assertEquals('order_' . $id, $this->testData[$callee]['request']['content']['order_id']);


        unset($this->testData[$callee]['request']['content']['order_id']);

        $this->setUpEsMockForRefundNotesNotFound();

        // Testing with Order Id
        $this->testData[$callee]['request']['content']['id'] = $id;

        $this->assertEquals($id, $this->testData[$callee]['request']['content']['id']);

        $this->assertResponseNotFound($callee);

        unset($this->testData[$callee]['request']['content']['id']);

        // Testing with RZP Refund Id
        $this->testData[$callee]['request']['content']['refund_id'] = 'rfnd_' . $id;

        $this->assertEquals('rfnd_' . $id, $this->testData[$callee]['request']['content']['refund_id']);

        $this->assertResponseNotFound($callee);

        unset($this->testData[$callee]['request']['content']['refund_id']);

        $this->setUpEsMockForRefundNotesNotFound();

        // Testing with Refund Id
        $this->testData[$callee]['request']['content']['id'] = $id;

        $this->assertEquals($id, $this->testData[$callee]['request']['content']['id']);

        $this->assertResponseNotFound($callee);

        unset($this->testData[$callee]['request']['content']['id']);

        $this->setUpEsMockForRefundNotesNotFound();

        // Testing with Merchant Transaction Id
        $this->testData[$callee]['request']['content']['id'] = $id;

        $this->assertEquals($id, $this->testData[$callee]['request']['content']['id']);

        $this->assertResponseNotFound($callee);

        unset($this->testData[$callee]['request']['content']['id']);

        // just resetting
        $this->ba->adminAuth('test');
    }

    public function assertInvalidIdResponse($callee, $id, $description)
    {
        $this->testData[$callee]['request']['content']['id'] = $id;
        $this->testData[$callee]['response']['content']['error']['description'] = $description;

        $this->runRequestResponseFlow($this->testData[$callee]);
    }

    public function setUpEsMockForRefundNotes($refund)
    {
        $esMock = $this->createEsMock(['search']);

        $expectedSearchParams = $this->testData['testSearchEsForRefundNotesExpectedSearchParams'];

        $expectedSearchRes = [
            'hits' => [
                'hits' => [
                    [
                        '_id' => substr($refund['id'], 5),
                    ]
                ],
            ],
        ];

        $expectedSearchParamsNotFound = [
            'hits' => [
                'hits' => [
                ],
            ],
        ];

        $esMock->expects($this->exactly(2))
            ->method('search')
            ->withConsecutive([$this->testData['testSearchEsForPaymentNotesExpectedSearchParamsNotFound']], [$expectedSearchParams])
            ->willReturnOnConsecutiveCalls($expectedSearchParamsNotFound, $expectedSearchRes);
    }

    public function setUpEsMockForPaymentNotes($paymentId)
    {
        $esMock = $this->createEsMock(['search']);

        $expectedSearchParams = $this->testData['testSearchEsForPaymentNotesExpectedSearchParams'];

        $expectedSearchRes = [
            'hits' => [
                'hits' => [
                    [
                        '_id' => $paymentId,
                    ]
                ],
            ],
        ];

        $esMock->expects($this->once())
            ->method('search')
            ->with($expectedSearchParams)
            ->willReturn($expectedSearchRes);
    }

    public function setUpEsMockForRefundNotesNotFound()
    {
        $esMock = $this->createEsMock(['search']);

        $expectedSearchParams = $this->testData['testSearchEsForRefundNotesExpectedSearchParamsNotFound'];

        $expectedSearchRes = [
            'hits' => [
                'hits' => [
                ],
            ],
        ];

        $expectedSearchParamsNotFound = [
            'hits' => [
                'hits' => [
                ],
            ],
        ];

        $esMock->expects($this->exactly(2))
            ->method('search')
            ->withConsecutive([$this->testData['testSearchEsForPaymentNotesExpectedSearchParamsAndNotFound']], [$expectedSearchParams])
            ->willReturnOnConsecutiveCalls($expectedSearchParamsNotFound, $expectedSearchRes);
    }

    public function assertMultiplePaymentsMultipleRefundsResponseFromOrderId($callee, $payment1, $payment2, $refund1, $refund2)
    {
        $this->ba->directAuth();

        $response = $this->runRequestResponseFlow($this->testData[$callee]);

        $refundEntity1 = $this->getDbEntityById('refund', $refund1['id']);
        $refundEntity2 = $this->getDbEntityById('refund', $refund2['id']);

        $paymentEntity1 = $this->getDbEntityById('payment', $payment1['id']);
        $paymentEntity2 = $this->getDbEntityById('payment', $payment2['id']);

        $autoRefundDelayDate = Carbon::createFromTimestamp(
            $paymentEntity1->getAuthorizeTimestamp() + $paymentEntity1->merchant->getAutoRefundDelay(), Timezone::IST);

        $autoRefundDelayDays = (int) (ceil($paymentEntity1->merchant->getAutoRefundDelay() / 86400));

        $payment1SecondaryMessage = 'Your payment was successfully recorded by Razorpay. ' .
            'If '.$paymentEntity1->merchant->getBillingLabel().' does not acknowledge the payment in ' . $autoRefundDelayDays .
            ' days an auto-refund would be initiated for the transaction by ' .
            $autoRefundDelayDate->toFormattedDateString() .
            '. You may contact the merchant for further details';

        $payment2SecondaryMessage = 'Your payment of ₹ 500 made towards ' .
            $paymentEntity1->merchant->getBillingLabel().' has been successful. We request you to contact ' .
            $paymentEntity1->merchant->getBillingLabel().' for any update on the service or to initiate a refund in case the service/goods were not delivered';

        $createdAtDate1 = Carbon::createFromTimestamp($refundEntity1->getCreatedAt(), Timezone::IST);
        $expectedDate1 = Holidays::getNthWorkingDayFrom($createdAtDate1, 7, true);
        $currentDate = Carbon::now(Timezone::IST);

        $days1 = $expectedDate1->diffInDays($currentDate, false);

        $createdAtDate2 = Carbon::createFromTimestamp($refundEntity2->getCreatedAt(), Timezone::IST);
        $expectedDate2 = Holidays::getNthWorkingDayFrom($createdAtDate2, 7, true);

        $days2 = $expectedDate2->diffInDays($currentDate, false);

        $secondaryMessage1 = 'The refund for the transaction of ' .$refundEntity1->getFormattedAmount() .' has been initiated';
        $secondaryMessage2 = 'Your refund for '.$refundEntity2->getFormattedAmount().' has been initiated by ' .
            $refundEntity2->merchant->getBillingLabel().'. The amount will be deposited in your bank account by ' .
            $expectedDate2->toFormattedDateString() ;

        $this->assertEquals($payment1SecondaryMessage, $response['payments'][0]['payment']['secondary_message']);
        $this->assertEquals($payment2SecondaryMessage, $response['payments'][1]['payment']['secondary_message']);

        $this->assertEquals($days1, $response['payments'][1]['refunds'][0]['days']);
        $this->assertEquals($secondaryMessage1, $response['payments'][1]['refunds'][0]['secondary_message']);

        $this->assertEquals($days2, $response['payments'][1]['refunds'][1]['days']);
        $this->assertEquals($secondaryMessage2, $response['payments'][1]['refunds'][1]['secondary_message']);
        $this->assertEquals('rzp_id', $response['id_type']);

        // just resetting
        $this->ba->adminAuth('test');
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdFailedPaymentCase()
    {
        // Failed Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $rzpPayment = $this->createFailedPayment($order);

        $this->resetMockServer();

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $rzpPayment['secondary_message'] = 'We are awaiting confirmation on the status of your payment from our Banking partners.';

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdPendingPaymentCase()
    {
        // Failed Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $rzpPayment = $this->createFailedPayment($order);

        $this->resetMockServer();

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $rzpPayment['secondary_message'] = 'We are awaiting confirmation on the status of your payment from our Banking partners.';

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdCreatedPaymentCase()
    {
        // Created Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $rzpPayment = $this->createFailedPayment($order);

        $this->resetMockServer();

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, [
            'notes' => [
                'order_id' => $merchantTransactionId
            ],
            'status' => 'created',
        ]);

        $rzpPayment['secondary_message'] = 'We are awaiting confirmation on the status of your payment from our Banking partners.';

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdLateAuthorizedCase()
    {
        // Late Authorized Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $rzpPayment = $this->createFailedPayment($order);

        $this->authorizeFailedPayment($rzpPayment['id']);

        $this->resetMockServer();

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $paymentEntity = $this->getDbEntityById('payment', $rzpPaymentId);

        $autoRefundDelayDate = Carbon::createFromTimestamp(
            $paymentEntity->getAuthorizeTimestamp() + $paymentEntity->merchant->getAutoRefundDelay(), Timezone::IST);

        $autoRefundDelayDays = (int) (ceil($paymentEntity->merchant->getAutoRefundDelay() / 86400));

        $rzpPayment['secondary_message'] = 'Your payment was successfully recorded by Razorpay. ' .
            'If '.$paymentEntity->merchant->getBillingLabel().' does not acknowledge the payment in ' . $autoRefundDelayDays .
            ' days an auto-refund would be initiated for the transaction by ' .
            $autoRefundDelayDate->toFormattedDateString() .
            '. You may contact the merchant for further details';

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdAuthorizedCase()
    {
        // Authorized Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $paymentEntity = $this->getDbEntityById('payment', $rzpPaymentId);

        $autoRefundDelayDate = Carbon::createFromTimestamp(
            $paymentEntity->getAuthorizeTimestamp() + $paymentEntity->merchant->getAutoRefundDelay(), Timezone::IST);

        $rzpPayment['secondary_message'] = 'Your payment was successfully recorded by Razorpay. ' .
            'If the services are not delivered by the merchant, an auto-refund would be initiated for the transaction by '
            . $autoRefundDelayDate->toFormattedDateString();

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdCapturedCase()
    {
        // Captured Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $paymentEntity = $this->getDbEntityById('payment', $rzpPaymentId);

        $rzpPayment['secondary_message'] = 'Your payment of ₹ 500 made towards '
            .$paymentEntity->merchant->getBillingLabel().' has been successful. '.
            'We request you to contact '.$paymentEntity->merchant->getBillingLabel().
            ' for any update on the service or to initiate a refund in case the '.
            'service/goods were not delivered';

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayIdLateAuthorizedCapturedCase()
    {
        // Late Authorized - Captured Payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $rzpPayment = $this->createFailedPayment($order);

        $this->authorizeFailedPayment($rzpPayment['id']);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $rzpPaymentId = PublicEntity::stripDefaultSign($rzpPayment['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $paymentEntity = $this->getDbEntityById('payment', $rzpPaymentId);

        $rzpPayment['secondary_message'] = 'Your payment of ₹ 500 made towards '
            .$paymentEntity->merchant->getBillingLabel().' has been successful. '.
            'We request you to contact '.$paymentEntity->merchant->getBillingLabel().
            ' for any update on the service or to initiate a refund in case the '.
            'service/goods were not delivered';

        $this->setUpEsMockForPaymentNotes($rzpPaymentId);

        $this->assertPaymentResponses(__FUNCTION__, $rzpPayment, $order, $merchantTransactionId);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCase()
    {
        // Failed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $this->gateway = 'hdfc';

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for ₹ 500 done on '.
            $refundEntity->merchant->getBillingLabel().
            ' is being processed and is taking longer than usual due to a technical issue at the bank\'s side.';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCaseTimePassed()
    {
        // Failed Refund >  t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $this->gateway = 'hdfc';

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Holidays::getNthWorkingDayFrom(Carbon::now(Timezone::IST), 7, true);

        Carbon::setTestNow($time);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for the transaction of ' . $refundEntity->getFormattedAmount() .
            ' has been initiated';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundWithArnCaseTimePassed()
    {
        // Processed Refund with ARN > t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $this->fixtures->edit('refund', substr($refund['id'], 5), [
            'reference1' => 'random_arn'
        ]);

        $time = Holidays::getNthWorkingDayFrom(Carbon::now(Timezone::IST), 7, true);

        Carbon::setTestNow($time);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for your payment done on '.
            $refundEntity->merchant->getBillingLabel().' for '.
            $refundEntity->getFormattedAmount().' has been processed by Razorpay. '.
            'Please contact your issuing bank for further details.';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundWithArnCase()
    {
        // Processed Refund with ARN < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $this->fixtures->edit('refund', substr($refund['id'], 5), [
            'reference1' => 'random_arn'
        ]);

        $createdAtDate = Carbon::createFromTimestamp($refund['created_at'], Timezone::IST);
        $expectedDate = Holidays::getNthWorkingDayFrom($createdAtDate, 7, true);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'Your refund for '.
            $refundEntity->getFormattedAmount().' has been initiated by ' . $refundEntity->merchant->getBillingLabel() .
            '. The amount will be deposited in your bank account by ' .
            $expectedDate->toFormattedDateString() ;

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCaseTimePassed()
    {
        // Processed Refund with ARN > t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Holidays::getNthWorkingDayFrom(Carbon::now(Timezone::IST), 7, true);

        Carbon::setTestNow($time);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for your payment done on '.
            $refundEntity->merchant->getBillingLabel() .' for '.
            $refundEntity->getFormattedAmount().' has been processed by Razorpay. '.
            'Please contact your issuing bank for further details.';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCase()
    {
        // Processed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Carbon::now(Timezone::IST);

        $time->addDay(3);

        Carbon::setTestNow($time);

        $createdAtDate = Carbon::createFromTimestamp($refund['created_at'], Timezone::IST);
        $expectedDate = Holidays::getNthWorkingDayFrom($createdAtDate, 7, true);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'Your refund for '.
            $refundEntity->getFormattedAmount().' has been initiated by ' . $refundEntity->merchant->getBillingLabel() .
            '. '. 'The amount will be deposited in your bank account by ' . $expectedDate->toFormattedDateString();

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testPaymentFetchDetailsForCustomerFromIdFetchFromUpiPayment()
    {
        $upiPayment = $this->testUpiPayment();

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], [
            'npci_reference_id' => '922114139332',
        ]);

        $paymentEntity = $this->getDbEntityById('payment', $upiEntity->getPaymentId());

        $secondaryMessage = 'Your payment of ' . $paymentEntity->getFormattedAmount() .
            ' made towards '.$paymentEntity->merchant->getBillingLabel().' has been successful. '.
            'We request you to contact '.$paymentEntity->merchant->getBillingLabel().
            ' for any update on the service or to initiate a refund in case the '.
            'service/goods were not delivered';

        $this->testData[__FUNCTION__]['request']['content']['id'] = '922114139332';

        $this->ba->directAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertArrayNotHasKey("merchant_id", $response['payments'][0]['payment']);
        $this->assertEquals('922114139332', $this->testData[__FUNCTION__]['request']['content']['id']);
        $this->assertEquals($upiPayment['id'], $response['payments'][0]['payment']['id']);
        $this->assertEquals($upiPayment['created_at'], $response['payments'][0]['payment']['created_at']);
        $this->assertEquals($secondaryMessage, $response['payments'][0]['payment']['secondary_message']);
        $this->assertEquals('npci_rrn', $response['id_type']);
    }

    public function testPaymentFetchDetailsForCustomerFromIdFetchFromUpiRefund()
    {
        $upiPayment = $this->testUpiPayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $this->refundPayment($upiPayment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], [
            'npci_reference_id' => '922114139332',
        ]);

        $createdAtDate = Carbon::createFromTimestamp($refundEntity->getCreatedAt(), Timezone::IST);
        $expectedDate = Holidays::getNthWorkingDayFrom($createdAtDate, 7, true);
        $currentDate = Carbon::now(Timezone::IST);

        $days = $expectedDate->diffInDays($currentDate, false);

        $secondaryMessage = 'Your refund for '.
            $refundEntity->getFormattedAmount().
            ' has been initiated by ' . $refundEntity->merchant->getBillingLabel() .
            '. The amount will be deposited in your bank account by '. $expectedDate->toFormattedDateString();

        $this->testData[__FUNCTION__]['request']['content']['id'] = '922114139332';

        $this->ba->directAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertEquals('922114139332', $this->testData[__FUNCTION__]['request']['content']['id']);
        $this->assertEquals($upiPayment['id'], $response['payments'][0]['payment']['id']);
        $this->assertEquals($upiPayment['created_at'], $response['payments'][0]['payment']['created_at']);

        $this->assertEquals($secondaryMessage, $response['payments'][0]['refunds'][0]['secondary_message']);
        $this->assertEquals($days, $response['payments'][0]['refunds'][0]['days']);
        $this->assertNotNull($response['payments'][0]['refunds'][0]['acquirer_data']['rrn']);
        $this->assertEquals('npci_rrn', $response['id_type']);
    }

    public function testPaymentFetchDetailsForCustomerFromRazorpayOrderIdMultiplePaymentsMultipleRefunds()
    {
        // 1 Late Authorized Payment and 1 captured payment
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment1 = $this->createFailedPayment($order);

        $this->resetMockServer();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $payment2 = $this->doAuthPayment($payment);

        $payment2 = $this->capturePayment($payment2['razorpay_payment_id'], $payment['amount']);

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund1 = $this->refundPayment($payment2['id'], $payment2['amount']/4,['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Holidays::getNthWorkingDayFrom(Carbon::now(Timezone::IST), 7, true);

        Carbon::setTestNow($time);

        $this->authorizeFailedPayment($payment1['id']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $refund2 = $this->refundPayment($payment2['id'], $payment2['amount']/2,['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $rzpPaymentId = PublicEntity::stripDefaultSign($payment1['id']);
        $merchantTransactionId = 'REZDELKJe2c92f0f46';

        $this->fixtures->edit('payment', $rzpPaymentId, ['notes' => [
            'order_id' => $merchantTransactionId
        ]]);

        $callee = __FUNCTION__;

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order['id'];

        $this->assertEquals($order['id'], $this->testData[__FUNCTION__]['request']['content']['order_id']);
        $this->assertMultiplePaymentsMultipleRefundsResponseFromOrderId($callee, $payment1, $payment2, $refund1, $refund2);

        unset( $this->testData[__FUNCTION__]['request']['content']['order_id']);

        $this->testData[__FUNCTION__]['request']['content']['id'] = PublicEntity::stripDefaultSign($order['id']);

        $this->assertEquals(PublicEntity::stripDefaultSign($order['id']), $this->testData[__FUNCTION__]['request']['content']['id']);
        $this->assertMultiplePaymentsMultipleRefundsResponseFromOrderId($callee, $payment1, $payment2, $refund1, $refund2);
    }

    public function testRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCaseForUSDPayment()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        // Failed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $this->fixtures->order->edit(PublicEntity::stripDefaultSign($order['id']), ['currency' => 'USD']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['currency'] = 'USD';

        $this->doAuthPayment($payment);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->capturePayment($rzpPayment['id'], $rzpPayment['amount'], 'USD');

        $this->gateway = 'hdfc';

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for $ 500 done on '.
            $refundEntity->merchant->getBillingLabel().
            ' is being processed and is taking longer than usual due to a technical issue at the bank\'s side.';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testVoidRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCase()
    {
        $this->fixtures->merchant->addFeatures('void_refunds');

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        // Processed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ],
            'order_id' => $order['id'],
        ]);

        $rzpPayment = $this->getLastEntity('payment', true);

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $this->fixtures->refund->edit($refund['id'], ['status' => 'created']);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for ₹ 500 done on '.
            $refundEntity->merchant->getBillingLabel().
            ' is being processed and is taking longer than usual due to a technical issue at the bank\'s side.';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testVoidRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCaseTimePassed()
    {
        $this->fixtures->merchant->addFeatures('void_refunds');

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        // Processed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ],
            'order_id' => $order['id'],
        ]);

        $rzpPayment = $this->getLastEntity('payment', true);

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Holidays::getNthWorkingDayFrom(Carbon::now(Timezone::IST), 7, true);

        Carbon::setTestNow($time);

        $this->fixtures->refund->edit($refund['id'], ['status' => 'created']);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'The refund for the transaction of ' . $refundEntity->getFormattedAmount() .
            ' has been initiated';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testVoidRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCaseTimePassed()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $this->fixtures->merchant->addFeatures('void_refunds');

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        // Processed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ],
            'order_id' => $order['id'],
        ]);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Holidays::getNthWorkingDayFrom(Carbon::now(Timezone::IST), 7, true);

        Carbon::setTestNow($time);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'Your refund for ' . $refundEntity->getFormattedAmount() .
            ' has been processed. If you have not received the refund credit yet, please contact our team by raising a request';

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testVoidRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCase()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $this->fixtures->merchant->addFeatures('void_refunds');

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'hitachi';

        $this->mockCardVault();

        // Processed Refund < t + 7
        $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ],
            'order_id' => $order['id'],
        ]);

        $rzpPayment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $merchantTransactionId = 'GOBUSANDe2c92f0f46';

        $refund = $this->refundPayment($rzpPayment['id'], $rzpPayment['amount'],['notes' =>
            ['pay_session_id' => '36752930', 'pay_txn_id' => '40982005', 'txnid' => $merchantTransactionId]]);

        $time = Carbon::now(Timezone::IST);

        $time->addDay(3);

        Carbon::setTestNow($time);

        $createdAtDate = Carbon::createFromTimestamp($refund['created_at'], Timezone::IST);
        $expectedDate = Holidays::getNthWorkingDayFrom($createdAtDate, 7, true);

        $refundEntity = $this->getDbEntityById('refund', $refund['id']);

        $refund['secondary_message'] = 'Your refund for '.
            $refundEntity->getFormattedAmount().' has been processed by ' . $refundEntity->merchant->getBillingLabel() .
            '. '. 'The amount will be deposited in your bank account by ' . $expectedDate->toFormattedDateString() ;

        $refunds = [$refund];

        $this->setUpEsMockForRefundNotes($refund);

        $this->assertRefundResponses(__FUNCTION__, $rzpPayment, $order, $refunds, $merchantTransactionId);
    }

    public function testCustomerFetchIdNotFound()
    {
        $this->assertResponsesNotFound(__FUNCTION__, 'CCPjoWzlDJG0g7');
    }

    public function testCustomerFetchInvalidId()
    {
        // 1. Atleast 1 digit required
        // 2. Spaces not allowed

        $invalidCases = [
            'Chandra'              => 'The id format is invalid.',
            'Chandra_'             => 'The id format is invalid.',
            'Chandra-'             => 'The id may only contain alphabets, digits and underscores.',
            'Chandra Reddy Layout' => 'The id may only contain alphabets, digits and underscores.',
        ];

        foreach ($invalidCases as $id => $description)
        {
            $this->assertInvalidIdResponse(__FUNCTION__, $id, $description);
        }
    }

    public function testInstantFailedOnGatewayUnsupportedRefundMessage()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        $scroogeResponse = [
            'mode' => 'IMPS',
            'gateway_refund_support' => false,
            'instant_refund_support' => true,
            'payment_age_limit_for_gateway_refund' => 180
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['fetchRefundCreateData', 'getRefund'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('fetchRefundCreateData')
                           ->willReturn($scroogeResponse);

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', false);

        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_requested']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);

        $this->fixtures->refund->edit($this->formatRefundId($refund['id']), ['status' => 'reversed', 'speed_processed' => null]);

        $scroogeResponse = [
            'body' => [
                'meta' => [
                    "payment_age_limit_for_gateway_refund" => 180
                ]
            ]
        ];

        $this->app->scrooge->method('getRefund')->willReturn($scroogeResponse);

        $this->testData[__FUNCTION__]['request']['content']['refund_id'] = $refund['id'];

        $this->ba->directAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertEquals('Your Refund has Failed', $response['payments'][0]['refunds'][0]['primary_message']);
        $this->assertEquals('The refund for the transaction of ₹ 34.71 has failed. Our banking partner does not support refund for this payment because it is more than 6 months old. The funds have been settled to Test Merchant, please contact Test Merchant to get it processed.',
            $response['payments'][0]['refunds'][0]['secondary_message']);
    }

    protected function enableRazorXTreatmentForFeature($featureUnderTest, $value = 'on')
    {
        $mock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $mock->method('getTreatment')
            ->will(
                $this->returnCallback(
                    function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                    {
                        return $feature === $featureUnderTest ? $value : 'control';
                    }));

        $this->app->instance('razorx', $mock);

    }
}
