<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Axis\EMandateDebitFileHeadings as Headings;
use RZP\Models\FileStore;
use Rzp\Models\Payment;

use Carbon\Carbon;

class Axis extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_AXIS;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::AXIS_EMANDATE_DEBIT;

    const FILE_NAME = 'Axis_EMandate_Debit';

    const STEP      = 'debit';

    protected function createGatewayEntity(Payment\Entity $payment): Netbanking\Base\Entity
    {
        $paymentId = $payment->getId();

        $gatewayPayment = new Netbanking\Base\Entity;

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAction(GatewayAction::AUTHORIZE);

        $gatewayPayment->setBank($payment->getBank());

        $merchant = $payment->merchant;

        if ($merchant->isTPVRequired() === true)
        {
            $gatewayPayment->setAccountNumber($payment->order->getAccountNumber());
        }

        $date = $date = Carbon::now(Timezone::IST)->format('d/m/Y H:m:s');

        $attr = [
            Netbanking\Base\Entity::MERCHANT_CODE => $payment->getMerchantId(),
            Netbanking\Base\Entity::AMOUNT        => $payment->getAmount(),
            Netbanking\Base\Entity::DATE          => $date,
        ];

        $gatewayPayment->fill($attr);

        $this->repo->netbanking->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function formatDataForFile($payments)
    {
        $mode = $this->app['basicauth']->getMode();

        $gateway = $this->app['gateway']->gateway(self::GATEWAY);

        $gateway->setMode($mode);

        $merchantId = $gateway->getEmandateMerchantId();

        $rows = [];

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $debitDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $row = [
                Headings::PAYMENT_ID                  => $paymentId,
                Headings::DEBIT_DATE                  => $debitDate,
                Headings::MERCHANT_ID                 => $merchantId,
                Headings::TOKEN_ID                    => $token['id'],
                Headings::CUSTOMER_NAME               => $token->customer['name'],
                Headings::DEBIT_ACCOUNT               => $token->getAccountNumber(),
                Headings::AMOUNT                      => $payment->getAmount(),
                Headings::ADDITIONAL_INFO_1           => '',
                Headings::ADDITIONAL_INFO_2           => '',
                Headings::UNDERLYING_REFERENCE_NUMBER => '',
            ];

            $rows[] = $row;
        }

        return $rows;
    }
}
