<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use DB;
use Mail;
use RZP\Constants\Table;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Customer\Token\Entity;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\PaymentsUpi\Vpa\Entity as Vpa;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class SavedVpaPaymentCreateTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiTrait;
    use DbEntityFetchTrait;
    use InteractsWithSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['save_vpa'], '10000000000000');

        $this->fixtures->create('customer:upi_payments_local_customer_token');

        $this->fixtures->create('customer:upi_payments_global_customer_token');

        $this->createUpiPaymentsGlobalCustomerVpa();

        $this->createUpiPaymentsLocalCustomerVpa();
    }

    public function testLocalSavedVpaPaymentCreateWithSaveFlag()
    {
        // To verify that save vpa flow does not get affected by validateVpa
        $this->validateVpa('vishnu@icici');
        $validated = $this->getDbLastEntity('payments_upi_vpa');

        // set payment data using token
        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $this->payment[Payment::SAVE] = true;

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment    = $this->getDbLastPayment();
        $token      = $this->getDbLastEntity('token');
        $vpa        = $this->getDbLastEntity('payments_upi_vpa');

        // Payments got created with save flag
        $this->assertArraySubset([
            Payment::GLOBAL_TOKEN_ID      => null,
            Payment::VPA                  => $vpa->getaddress(),
            Payment::CUSTOMER_ID          => '100000customer',
            Payment::GLOBAL_CUSTOMER_ID   => null,
            Payment::STATUS               => 'authorized',
            Payment::GATEWAY              => 'sharp',
        ], $payment->toArray());

        // Token got created with save flag
        $this->assertArraySubset([
            Entity::ID                      => $payment->getTokenId(),
            Entity::METHOD                  => 'upi',
            Entity::VPA_ID                  => $vpa->getId(),

        ], $token->toArray());

        // Vpa got created against the token
        $this->assertArraySubset([
            Vpa::ID                         => $validated->getId(),
            Vpa::USERNAME                   => 'vishnu',
            Vpa::HANDLE                     => 'icici',
            Vpa::NAME                       => null,
        ], $vpa->toArray());

        $record = DB::connection('payments_upi_test')->table(Table::PAYMENTS_UPI_VPA)->find($vpa->getId());
        // VPA actually exists in the payments_upi database, test in this case
        $this->assertSame($vpa->getId(), $record->id);

        $tokens = $this->getEntities('token', ['method' => 'upi'], true);

        // Admin fetch returning the newly created VPA
        $this->assertSame($token->getPublicId(), $tokens['items'][0]['id']);

        // Relation VPA is now resolved.
        $this->assertArraySubset([
            Vpa::USERNAME                   => 'vishnu',
            Vpa::HANDLE                     => 'icici',
            Vpa::NAME                       => false,
        ], $tokens['items'][0]['vpa']);
    }

    public function testLocalSavedVpaPaymentCreateWithToken()
    {
        // set payment data using token
        $this->payment = $this->getDefaultUpiPaymentArray();

        unset($this->payment['vpa']);

        $this->payment[Payment::TOKEN] = '10000upitoken';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment    = $this->getDbLastPayment();
        $token      = $this->getEntityById('token', '1000000custupi', true);
        $vpa        = $this->getEntityById('payments_upi_vpa', $token['vpa_id'], true);

        $this->assertArraySubset([
            Payment::GLOBAL_TOKEN_ID      => null,
            Payment::VPA                  => $vpa[Vpa::USERNAME] . '@' . $vpa[Vpa::HANDLE],
            Payment::CUSTOMER_ID          => '100000customer',
            Payment::GLOBAL_CUSTOMER_ID   => null,
            Payment::STATUS               => 'authorized',
            Payment::GATEWAY              => 'sharp',
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ID                      => 'token_' . $payment->getTokenId(),
            Entity::METHOD                  => 'upi',
            Entity::VPA_ID                  => $vpa['id'],
        ], $token);
    }

    public function testGlobalSavedVpaPaymentCreate()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');
    }

    public function testGlobalSavedVpaPaymentCreateWithSaveMethod()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->payment[Payment::SAVE] = true;

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment    = $this->getDbLastPayment();
        $token      = $this->getDbLastEntity('token');
        $vpa        = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertArraySubset([
            Payment::GLOBAL_TOKEN_ID      => $token->getId(),
            Payment::VPA                  => $vpa[Vpa::USERNAME] . '@' . $vpa[Vpa::HANDLE],
            Payment::CUSTOMER_ID          => null,
            Payment::GLOBAL_CUSTOMER_ID   => '10000gcustomer',
            Payment::STATUS               => 'authorized',
            Payment::GATEWAY              => 'sharp',
            Payment::APP_TOKEN            => '1000000custapp'
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ID                      => $payment->getGlobalTokenId(),
            Entity::METHOD                  => 'upi',
            Entity::VPA_ID                  => $vpa->getId(),
        ], $token->toArray());

        $this->assertArraySubset([
            Vpa::USERNAME                   => 'vishnu',
            Vpa::HANDLE                     => 'icici',
            Vpa::NAME                       => null,
        ], $vpa->toArray());
    }

    public function testGlobalSavedVpaPaymentCreateWithToken()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultUpiPaymentArray();

        unset($this->payment['vpa']);

        $this->payment[Payment::TOKEN] = '10000gupitoken';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], '100000custgupi');

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = ['test_app_token' => $appToken];

        $this->session($data);
    }
}
