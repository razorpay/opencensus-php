<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\EMandate;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

abstract class Base extends EMandate\Base
{
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

    public function generateData(PublicCollection $payments)
    {
        try
        {
            // Set $this->data for later use
            $this->data = $payments;

            // Create gateway entities
            $this->createGatewayEntities($payments);

            return $this->data;
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                [
                    'id' => $this->gatewayFile->getId()
                ]);
        }
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

            $gatewayPayment = $this->createGatewayEntity($payment);
        }
    }
}
