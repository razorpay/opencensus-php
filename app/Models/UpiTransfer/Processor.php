<?php

namespace RZP\Models\UpiTransfer;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Constants\Entity as Constants;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

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

        $payeeVpa = $this->gatewayInput[GatewayResponseParams::PAYEE_VPA];

        $amount = $this->gatewayInput[GatewayResponseParams::AMOUNT];

        $upiTransferEntity = $this->repo->upi_transfer->findByProviderReferenceIdAndPayeeVpaAndAmount(
                                                                                                      $providerReferenceId,
                                                                                                      $payeeVpa,
                                                                                                      $amount);

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

                    Tracer::inSpan(['name' => HyperTrace::UPI_TRANSFER_PROCESS_PAYMENT],
                        function() use ($upiTransfer, $paymentInput)
                    {
                        $this->createPaymentOrUnexpected($upiTransfer, $paymentInput, $this->callbackData);
                    });

                    $payment = $this->getPaymentProcessor()->getPayment();

                    $upiTransfer->payment()->associate($payment);

                    $upiTransfer->virtualAccount()->associate($this->virtualAccount);

                    $this->repo->saveOrFail($upiTransfer);

                    $this->updateVirtualAccount($upiTransfer);

                    return $payment;
                }
            );

            Tracer::inSpan(['name' => HyperTrace::UPI_TRANSFER_CAPTURE_OR_REFUND], function() use ($upiTransfer)
            {
                $this->refundOrCapturePayment($upiTransfer);
            });

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

        $vpa = $this->repo->vpa->findByAddressAndEntityTypes($payeeVpa, [Constants::VIRTUAL_ACCOUNT], true);

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

        if (array_key_exists(GatewayResponseParams::PAYER_ACCOUNT_TYPE, $this->gatewayInput) === true)
        {
            $paymentArray[Payment\Entity::PAYER_ACCOUNT_TYPE] = $this->gatewayInput[GatewayResponseParams::PAYER_ACCOUNT_TYPE];
        }

        $merchant = $this->virtualAccount->merchant;

        if ($merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $paymentArray[Payment\Entity::FEE] = $this->getFees($upiTransfer);
        }

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

    protected function createUnexpectedPayment(Base\PublicEntity $upiTransfer, array $gatewayData = [])
    {
        $this->virtualAccount = (new VirtualAccount\Core())->createOrFetchSharedVirtualAccount();

        $this->setMerchant();

        $upiTransfer->virtualAccount()->associate($this->virtualAccount);

        $input = $this->getPaymentArray($upiTransfer);

        $this->paymentProcessor = new Payment\Processor\Processor($this->merchant);

        return $this->createPayment($input, $gatewayData);
    }

    protected function getFees(Base\PublicEntity $upiTransfer)
    {
        $parentPaymentArray = $this->getReceiverPaymentArray();

        $paymentArray = [
            Payment\Entity::CURRENCY => Currency::INR,
            Payment\Entity::METHOD   => $upiTransfer->getMethod(),
            Payment\Entity::AMOUNT   => $upiTransfer->getAmount(),
            Payment\Entity::VPA      => $upiTransfer->getPayerVpa(),
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        $paymentProcessor = new PaymentProcessor($this->virtualAccount->merchant);

        $data = $paymentProcessor->processAndReturnFees($paymentArray);

        return $data['fees'];
    }
}
