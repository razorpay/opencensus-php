<?php


namespace Functional\PaymentsUpi\Service;


use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Tests\Functional\PaymentsUpi\Service\UpiPaymentServiceReconBase;

class UpiRzpapbPaymentServiceReconTest extends UpiPaymentServiceReconBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_rzpapb';

        $this->terminal = $this->fixtures->create('terminal:upi_rzpapb_terminal');
    }

    /** test post recon entity update
     * @return void
     * @throws \Exception
     */
    public function testUpdatePostReconData()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $this->makeCallbackForPayment($payment, true);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::AUTHORIZED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => '',
                Entity::INTERNAL_ERROR_CODE => '',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $payment->getId();

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $upiEntity = $this->getDbLastEntity('upi');

        // Assert empty reconciledAt in gateway entity
        $this->assertEmpty($upiEntity['reconciled_at']);
    }
}
