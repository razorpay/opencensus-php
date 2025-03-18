<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Order\Entity as orderEntity;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use Illuminate\Http\UploadedFile;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Models\Batch\Status as BatchStatus;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Exception\P2p\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException as BaseBadRequestValidationFailureException;
use RZP\Exception\BadRequestException as BaseBadRequestException;

class UpiAxisOlivePaymentServiceTest extends UpiPaymentServiceTest
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = 'upi_axisolive';
    }


    public function testTpvOrderCreateAndAssertPreferencesResponse()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $preferences = $this->getTurboPreferences($order[Entity::ID], '');

        $this->assertArrayHasKey('tpv', $preferences);

        $this->assertArrayHasKey('restrict_bank_accounts', $preferences["tpv"]);

        $this->assertArrayHasKey('bank_accounts', $preferences["tpv"]);

        $this->assertArrayHasKey('account_number', $preferences["tpv"]["bank_accounts"][0]);

        $this->assertEquals('XXXXXXXXXX0304', $preferences["tpv"]["bank_accounts"][0]['account_number']);
    }

    // merchant is tpv enabled and order id is not passed
    // only tpv flags should come up
    public function testTpvOrderCreateAndAssertPreferencesResponseWithoutOrderId()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $preferences = $this->getTurboPreferences('', '');

        $this->assertArrayNotHasKey('tpv', $preferences);


    }

    public function testTpvPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $preferences = $this->getTurboPreferences($order[Entity::ID], '');

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $data = $this->doAjaxPaymentWithUps('terminal:shared_upi_axisolive_tpv_terminal', 'upi_axisolive');

        $this->gateway = 'upi_axisolive';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $payment = $this->getDbLastPayment()->toArray();
    }

    // This test requires the correctness of the test testTpvPaymentSuccess() for a lot of assertions related to TPV
    // This test mocks the asserts done inside the UPS Service mock to focus on the account number assertion.
    public function testTpvPaymentSuccessWithAccountNumberFromBankAccount()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->fixtures->edit('order', str_after($order['id'], 'order_'), ['account_number' => null]);

        $preferences = $this->getTurboPreferences($order[Entity::ID], '');

        $this->payment['amount']      = $order['amount'];
        $this->payment['order_id']    = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $modifiedAccNumber = null;

        $this->upiPaymentService->shouldReceive('action')->andReturnUsing(
            function ($action, $input, $gateway) use (&$modifiedAccNumber) {
                $modifiedAccNumber = $input['order']['bank_account']['account_number'];

                return [
                    'data' => ['vpa' => 'razorpay@airtel'],
                    'gateway' => 'upi_axisolive',
                ];
            }
        );

        $data = $this->doAjaxPaymentWithUps('terminal:shared_upi_axisolive_tpv_terminal', 'upi_axisolive');

        $this->gateway = 'upi_axisolive';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->assertEquals('created', $payment->getStatus());

        $this->assertEquals('04030403040304', $payment->order->bankAccount->account_number);
        $this->assertEquals('0403040304', $modifiedAccNumber);
    }


    public function testTpvInvalidOrderId()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->expectException(BaseBadRequestValidationFailureException::class);

        $this->expectExceptionMessage(orderEntity::stripDefaultSign($order[Entity::ID])."xyz is not a valid id");

        $preferences = $this->getTurboPreferences($order[Entity::ID]."xyz", '');
    }

    public function testNonTpvOrderCreateAndAssertPreferencesResponse()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->expectException(BaseBadRequestException::class);

        $order = $this->createTpvOrderWithoutBankAccount();
    }
}
