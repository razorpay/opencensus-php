<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;
use Illuminate\Http\UploadedFile;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiPaymentServiceReconTest extends UpiPaymentServiceTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Authorize the failed payment by force authorizing it
     */
    public function testAuthorizeFailedPaymentForUpiSbi()
    {
        $this->gateway = 'upi_sbi';

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiSbiPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('123456789013', $updatedPayment['reference16']);

        $this->assertEquals('razor.pay@sbi', $updatedPayment['vpa']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/upi/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }


    private function makeUpiSbiPaymentsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiSbiPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiSbiPayment()
    {
            $attributes = [
                    'terminal_id'       => $this->terminal->getId(),
                    'method'            => 'upi',
                    'amount'            => $this->payment['amount'],
                    'base_amount'       => $this->payment['amount'],
                    'amount_authorized' => $this->payment['amount'],
                    'status'            => 'failed',
                    'gateway'           => $this->gateway,
                    'cps_route'         => 4,
                ];

            $payment = $this->fixtures->create('payment', $attributes);

            $transaction = $this->fixtures->create('transaction',
                    ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

            $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        return $payment->getId();
    }
}
