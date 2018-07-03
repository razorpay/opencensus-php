<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\EMandate;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

abstract class Base extends EMandate\Base
{
    protected $gatewayRepo;

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
            $data = $payments;

            // Create gateway entities
            $this->createGatewayEntities($payments);

            return $data;
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function createGatewayEntities(PublicCollection $payments)
    {
        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $gatewayPayment = $this->gatewayRepo->findByPaymentIdAndAction(
                                    $paymentId, GatewayAction::AUTHORIZE);

            //
            // If gatewayPayment already exists then skip its creation.
            // This case will arise when we retry sending some payments to the bank
            //
            if ($gatewayPayment !== null)
            {
                continue;
            }

            $this->createGatewayEntity($payment);
        }
    }

    protected function createGatewayEntity(Payment\Entity $payment)
    {
        $paymentId = $payment->getId();

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAction(GatewayAction::AUTHORIZE);

        $gatewayPayment->setBank($payment->getBank());

        $gatewayPayment->setAmount($payment->getAmount());

        $attributes = $this->getGatewayAttributes($payment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    /**
     * Override this method in the child classes in case you want
     * to add extra values in the gateway entity
     *
     * @param Payment\Entity $payment
     *
     * @return array
     */
    protected function getGatewayAttributes(Payment\Entity $payment): array
    {
        $date = Carbon::now(Timezone::IST)->format('d/m/Y H:m:s');

        $merchant = $payment->merchant;

        $attributes = [
            Netbanking\Base\Entity::MERCHANT_CODE => $payment->getMerchantId(),
            Netbanking\Base\Entity::DATE          => $date,
        ];

        if ($merchant->isTPVRequired() === true)
        {
            $attributes[Netbanking\Base\Entity::ACCOUNT_NUMBER] = $payment->order->getAccountNumber();
        }

        return $attributes;
    }
}
