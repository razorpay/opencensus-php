<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Processor\EMandate\Base;
use Rzp\Models\Payment;
use RZP\Trace\TraceCode;

class Axis extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_AXIS;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::AXIS_EMANDATE_DEBIT;

    const FILE_NAME = 'Axis_EMandate_Debit';

    const STEP      = 'debit';

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $payments = $this->repo->payment->fetchPendingEMandateDebit(static::GATEWAY, $begin, $end);

        $paymentIds = $payments->pluck(Payment\Entity::ID)->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $payments;
    }

    public function formatDataForFile()
    {
        $rows = [];

        return $rows;
    }

    public function generateData(PublicCollection $payments)
    {
//        try
//        {
//            // Set $this->data for later use
            $this->data = $payments;
//
//            // Create gateway entities
//            $this->createGatewayEntities($payments);
//
//            return $this->data;
//        }
//        catch (\Throwable $e)
//        {
//            throw new GatewayFileException(ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA);
//        }
    }
//
//    protected function createGatewayEntities(PublicCollection $payments)
//    {
//        foreach ($payments as $payment)
//        {
//            $paymentId = $payment->getId();
//
//            $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction(
//                $paymentId, GatewayAction::AUTHORIZE);
//
//            //
//            // If gatewayPayment already exists then skip its creation.
//            // This case will arise when we retry sending some payments to the bank
//            //
//            if ($gatewayPayment !== null)
//            {
//                continue;
//            }
//
//            $gatewayPayment = new Netbanking\Base\Entity;
//
//            $gatewayPayment->setPaymentId($paymentId);
//
//            $gatewayPayment->setAction(GatewayAction::AUTHORIZE);
//
//            $gatewayPayment->setBank($payment->getBank());
//
//            $merchant = $payment->merchant;
//
//            if ($merchant->isTPVRequired() === true)
//            {
//                $gatewayPayment->setAccountNumber($payment->order->getAccountNumber());
//            }
//
//            $date = $date = Carbon::now(Timezone::IST)->format('d/m/Y H:m:s');
//
//            $attr = [
//                'client_code'       => $this->getClientCode($payment),
//                'merchant_code'     => $payment->getMerchantId(),
//                'amount'            => $payment->getAmount(),
//                'date'              => $date,
//            ];
//
//            $gatewayPayment->fill($attr);
//
//            $this->repo->netbanking->saveOrFail($gatewayPayment);
//        }
//    }
}
