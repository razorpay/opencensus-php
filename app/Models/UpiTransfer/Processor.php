<?php

namespace RZP\Models\UpiTransfer;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;

class Processor extends VirtualAccount\Processor
{
    protected $gatewayInput;

    protected $callbackData;

    protected $terminal;

    public function __construct(array $gatewayResponse, $terminal)
    {
        parent::__construct();

        $this->gatewayInput = $gatewayResponse['upi_transfer_data'];

        $this->callbackData = $gatewayResponse['callback_data'];

        $this->terminal = $terminal;
    }

    protected function isDuplicate(Base\PublicEntity $upiTransfer)
    {
        $providerReferenceId = $this->gatewayInput[GatewayResponseParams::PROVIDER_REFERENCE_ID];

        $upiTransferEntity = $this->repo->upi_transfer->findByProviderReferenceId($providerReferenceId);

        if ($upiTransferEntity === null)
        {
            return false;
        }

        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_DUPLICATE_NOTIFICATION,
            [
                'Existing upi transfer'   => $upiTransferEntity->getPublicId(),
                'Received bank reference' => $upiTransfer->getBankReference(),
            ]);

        return true;
    }

    protected function processPayment(Base\PublicEntity $upiTransfer)
    {
        try
        {
            $this->repo->transaction(
                function() use ($upiTransfer) {

                    $paymentInput = $this->getPaymentArray($upiTransfer);

                    $this->callbackData[Payment\Entity::TERMINAL_ID] = $this->getTerminal()->getId();

                    $this->createPayment($paymentInput, $this->callbackData);

                    $payment = $this->getPaymentProcessor()->getPayment();

                    $upiTransfer->payment()->associate($payment);

                    $upiTransfer->virtualAccount()->associate($this->virtualAccount);

                    $this->repo->saveOrFail($upiTransfer);

                    $this->updateVirtualAccount($upiTransfer);

                    return $payment;
                }
            );

            $this->refundOrCapturePayment($upiTransfer);

            return $upiTransfer;
        }
        catch (\Exception $ex)
        {
            $this->app['diag']->trackUpiTransferEvent(
                EventCode::UPI_TRANSFER_UNEXPECTED_PAYMENT,
                $upiTransfer,
                $ex,
                ['error' => $ex->getMessage()]
            );

            throw $ex;
        }

    }

    protected function getVirtualAccountFromEntity(Base\PublicEntity $entity)
    {
        $payeeVpa = $entity->getPayeeVpa();

        $vpa = $this->repo->vpa->findByAddress($payeeVpa);

        if ($vpa === null)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromVpaId($vpa->getId());

        return $virtualAccount;
    }

    protected function getReceiver()
    {
        return $this->virtualAccount->vpa;
    }

    protected function getTerminal()
    {
        return $this->terminal;
    }

    protected function getPaymentArray(Base\PublicEntity $upiTransfer): array
    {
        $parentPaymentArray = $this->getDefaultPaymentArray();

        $paymentArray = [
            Payment\Entity::CURRENCY => Currency::INR,
            Payment\Entity::METHOD   => $upiTransfer->getMethod(),
            Payment\Entity::AMOUNT   => $upiTransfer->getAmount(),
            '_'                      => [
                Payment\Analytics\Entity::LIBRARY => Payment\Analytics\Metadata::PUSH,
            ],
            Payment\Entity::NOTES    => $this->virtualAccount->getNotes()->toArray(),
            Payment\Entity::VPA      => $upiTransfer->getPayerVpa(),
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        //TODO::Check for VPA pricing
        return $paymentArray;
    }

    protected function useSharedVirtualAccount(Base\PublicEntity $upiTransfer): bool
    {
        if ($this->virtualAccount === null)
        {
            $upiTransfer->setUnexpectedReason(self::VIRTUAL_ACCOUNT_NOT_FOUND);

            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_UNEXPECTED_PAYMENT,
                [
                    'entity' => [
                        Entity::PAYER_VPA      => $upiTransfer->getPayerVpa(),
                        Entity::PAYEE_VPA      => $upiTransfer->getPayeeVpa(),
                        Entity::AMOUNT         => $upiTransfer->getAmount(),
                        Entity::BANK_REFERENCE => $upiTransfer->getBankReference(),
                    ],
                ]);

            $this->app['diag']->trackUpiTransferEvent(
                EventCode::UPI_TRANSFER_UNEXPECTED_PAYMENT,
                $upiTransfer,
                null,
                ['error' => self::VIRTUAL_ACCOUNT_NOT_FOUND]
            );

            return true;
        }

        return parent::useSharedVirtualAccount($upiTransfer);
    }
}
