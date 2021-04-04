<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Corporation;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Gateway\Netbanking\Corporation;
use RZP\Models\Payment;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base\Repository as NetbankingRepository;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class NetbankingCorporationGatewayTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;

    // Hardcoding these to add the reconciliation behaviour
    const CUSTOMER_ACCOUNT_BR_CODE  = '4321';
    const CUSTOMER_ACCOUNT_NUMBER   = '987654';
    const CUSTOMER_ACCOUNT_TYPE     = 'SB';
    const CUSTOMER_ACCOUNT_SUB_TYPE = '01';

    protected function setUp(): void
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__.'/NetbankingCorporationGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_corporation';

        $this->bank = 'CORP';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');
    }

    public function testPayment()
    {
        $this->doNetbankingCorporationAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment
        );

        $this->assertArrayHasKey('bank_payment_id', $payment);
    }

    public function testPartnerPayment()
    {
        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'CORP';

        $this->doPartnerAuthPayment($payment, $clientId, $submerchantId);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame('authorized', $payment['status']);
    }

    /**
     * Test a payment that was tampered with in the authorize step
     * This case should throw PaymentVerificationException during verify broken
     */
    public function testTamperedPayment()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedVerifyResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->doNetbankingCorporationAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doNetbankingCorporationAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAuthFailedVerifySuccess()
    {
        $this->testAuthorizeFailed();

        $data = $this->testData['testVerifyMismatch'];

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            }
        );

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifySuccessEntity');
    }

    public function testTpvPayment()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['amount'] = $order['amount'];

        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment[Payment\Entity::STATUS], Payment\Status::AUTHORIZED);

        $this->assertEquals($payment['terminal_id'], '100NbCorpTrmnl');

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
                            $data['request']['content']['account_number']);

        $this->assertEquals('S', $gatewayEntity['status']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testRefundExcelFile()
    {
        Mail::fake();

        $payments = [];

        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $payments[] = $payment['id'];

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $payments[] = $payment['id'];

        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;

            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doNetbankingCorporationAuthAndCapturePayment();

        $payments[] = $payment['id'];

        $this->updateAccountDetailsInNetbankingEntity($payments);

        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForNb('CORP');

        $this->assertEquals($data['netbanking_corporation']['count'], 3);

        $this->assertTrue(file_exists($data['netbanking_corporation']['file']));

        unlink($data['netbanking_corporation']['file']);
    }

    protected function doNetbankingCorporationAuthAndCapturePayment($order = [])
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'CORP';

        if (empty($order) === false)
        {
            $payment['order_id'] = $order['id'];
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    Corporation\ResponseFields::VERIFY_RESULT => Corporation\ResponseCodeMap::RESULT_REJECTED
                ];
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[Corporation\ResponseFields::STATUS] = Corporation\ResponseCodeMap::FAILURE_CODE;

                unset($content[Corporation\ResponseFields::BANK_REF_NUMBER]);
            }
        });
    }

    // In reconciliation, we get more details and add it to the gateway entities.
    // Here we manually update these values with hardcoded ones to emulate this behaviour
    protected function updateAccountDetailsInNetbankingEntity(array $payments)
    {
        foreach ($payments as $id)
        {
            $paymentId = Payment\Entity::verifyIdAndStripSign($id);

            $repo = new NetbankingRepository();

            $gatewayPayment = $repo->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);

            $gatewayPayment->fill(
                [
                    NetbankingEntity::ACCOUNT_NUMBER     => self::CUSTOMER_ACCOUNT_NUMBER,
                    NetbankingEntity::ACCOUNT_TYPE       => self::CUSTOMER_ACCOUNT_TYPE,
                    NetbankingEntity::ACCOUNT_SUBTYPE    => self::CUSTOMER_ACCOUNT_SUB_TYPE,
                    NetbankingEntity::ACCOUNT_BRANCHCODE => self::CUSTOMER_ACCOUNT_BR_CODE,
                ]
            );

            $gatewayPayment->saveOrFail();
        }
    }

    protected function mockFailedCallbackAndVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[Corporation\ResponseFields::STATUS] = Corporation\ResponseCodeMap::FAILURE_CODE;

                unset($content[Corporation\ResponseFields::BANK_REF_NUMBER]);
            }
            else if ($action === 'verify')
            {
                $content = [
                    Corporation\ResponseFields::VERIFY_RESULT => Corporation\ResponseCodeMap::RESULT_REJECTED
                ];
            }
        });
    }
}
