<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Processor\EMandate\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class NetbankingHdfc extends Base
{
    const DAILY         = 'daily';
    const MAX_END_DATE  = '31/12/2099';

    const STEP          = 'debit';
    const GATEWAY       = Payment\Gateway::NETBANKING_HDFC;
    const FILE_NAME     = 'HDFC_EMandate_Debit';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::HDFC_EMANDATE_DEBIT;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $gateway = $this->gatewayFile->getTarget();

        $payments = $this->repo->payment->fetchPendingEMandateDebit($gateway, $begin, $end);

        $paymentIds = $payments->pluck(Payment\Entity::ID)->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_REQUEST,
            [
                'payment_ids'   => $paymentIds,
            ]);

        return $payments;
    }

    public function generateData(PublicCollection $payments)
    {
        // Set $this->data for later use
        $this->data = $payments;

        // Create gateway entities
        $this->createGatewayEntities($payments);

        return $this->data;
    }

    protected function createGatewayEntities(PublicCollection $payments)
    {
        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction(
                                    $paymentId, GatewayAction::AUTHORIZE);

            //
            // If gatewayPayment already exists then skip its creation.
            // This case will arise when we retry sending some payments to the bank
            //
            if ($gatewayPayment !== null)
            {
                continue;
            }

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
                'client_code'       => $this->getClientCode($payment),
                'merchant_code'     => $payment->getMerchantId(),
                'amount'            => $payment->getAmount(),
                'date'              => $date,
            ];

            $gatewayPayment->fill($attr);

            $this->repo->netbanking->saveOrFail($gatewayPayment);
        }
    }

    protected function getClientCode(Payment\Entity $payment): string
    {
        $email = $payment->getEmail() ?: Payment\Entity::DUMMY_EMAIL;

        $clientCode = $this->stripEmailSpecialChars($email);

        return $clientCode;
    }

    protected function formatDataForFile()
    {
        $payments = $this->data;

        $rows = [];

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $startDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $row = [

                Headings::TRANSACTION_REF_NO  => $paymentId,
                Headings::MANDATE_ID          => $token->getId(),
                Headings::ACCOUNT_NO          => $token->getAccountNumber(),
                Headings::AMOUNT              => $this->getFormattedAmount($payment->getAmount()),
                Headings::SIP_DATE            => $startDate,
                Headings::FREQUENCY           => self::DAILY,
                Headings::FROM_DATE           => $startDate,
                Headings::TO_DATE             => self::MAX_END_DATE,
            ];

            $rows[] = $row;
        }

        $this->trace->info(TraceCode::EMANDATE_DEBIT_REQUEST_FILE_DATA, ['rows' => $rows]);

        return $rows;
    }

    protected function stripEmailSpecialChars($email)
    {
        return preg_replace("/[^a-zA-Z0-9]+/", "", $email);
    }
}