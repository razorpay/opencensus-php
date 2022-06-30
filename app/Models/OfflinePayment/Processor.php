<?php

namespace RZP\Models\OfflinePayment;

use Mail;
use Cache;
use Config;
use Request;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\OfflineChallan;
use RZP\Models\OfflinePayment;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankTransfer\HdfcEcms\StatusCode;

class Processor extends VirtualAccount\Processor
{
    protected function isDuplicate(Base\PublicEntity $offlinePayment): bool
    {

        $challan_number = $offlinePayment->getChallanNumber();


        $duplicateOfflinePayment = $this->repo->offline_payment->fetchByChallanNumber($challan_number);

        if ($duplicateOfflinePayment !== null)
        {
            $this->trace->error(
                TraceCode::OFFLINE_PAYMENT_DUPLICATE_REQUEST,
                [
                    'message'               => 'Duplicate offline payment request received',
                    'challan_number'        => $challan_number,
                ]
            );

            return true;
        }


        return false;
    }

    /**
     * Processing the bank transfer for both PG and Banking scenarios.
     *
     * Common
     *  - Create bank transfer, associate with the merchant, and the identified VA
     *  - Create payer bank account, associate with bank transfer
     * PG:
     *  - Create payment (and associated txn), associate with the bank transfer
     *  - Update VA amount fields and status, if necessary
     * BB:
     *  - Create transaction, associate with bank_transfers
     *
     * @param  Base\PublicEntity $bankTransfer
     * @return null|Base\PublicEntity
     */
    protected function processPayment(Base\PublicEntity $offlinePayment)
    {
        if ($offlinePayment->getUnexpectedReason() !== null) {
            return null;
        }

        $offlinePayment = $this->repo->transaction(function () use ($offlinePayment) {

            $offlinePayment->merchant()->associate($this->merchant);

            $offlinePayment->virtualAccount()->associate($this->virtualAccount);

            $this->processPaymentForPg($offlinePayment);

            return $offlinePayment;

        });


        $offlinePayment->setExpected(false);
        $this->refundOrCapturePayment($offlinePayment);

        return $offlinePayment;
    }

    protected function processPaymentForPg(Entity $offlinePayment)
    {

            // Prepares payment input and creates payment and its transaction etc.
            $paymentInput = $this->getPaymentArray($offlinePayment);

            $params['gateway_merchant_id'] = $offlinePayment->getClientCode();
            $params['offline'] = true;
            $params['merchant_id'] = $offlinePayment->merchant->getId();

            $auth = (new OfflinePayment\Entity())->getAuth();

        switch ($auth) {
            case 'hdfc_otc':
                $params['gateway'] = 'offline_hdfc';
                break;
        }


            $terminals = $this->repo->terminal->getByParams($params);

            if($terminals->count() === 0)
            {
                throw new LogicException('No terminal found for offline.');
            }

            $gatewayData[Payment\Entity::TERMINAL_ID] = $terminals->getIds();

            $paymentInput[Payment\Entity::RECEIVER][Payment\Entity::ID] = 'ch_'.$paymentInput[Payment\Entity::RECEIVER][Payment\Entity::ID];

            $this->createOfflinePayment($offlinePayment, $paymentInput, $gatewayData);

            $payment = $this->getPaymentProcessor()->getPayment();

            $offlinePayment->payment()->associate($payment);

            unset($offlinePayment[Entity::EXPECTED]);

            if (isset($offlinePayment['payment_instrument_details']))
            {
                $pid = json_encode($offlinePayment['payment_instrument_details']) ?? null;
                unset($offlinePayment['payment_instrument_details']);
                $offlinePayment['payment_instrument_details'] = $pid;
            }

            if (isset($offlinePayment['payer_details']))
            {
                $pid = json_encode($offlinePayment['payer_details']);
                unset($offlinePayment['payer_details']);
                $offlinePayment['payer_details'] = $pid;
            }

            $this->repo->saveOrFail($offlinePayment);


            $this->trace->info(TraceCode::OFFLINE_CREATED,
            [
                Entity::VIRTUAL_ACCOUNT_ID     => $this->virtualAccount->getId(),
                Entity::CHALLAN_NUMBER         => $offlinePayment[Entity::CHALLAN_NUMBER],
                Entity::UNEXPECTED_REASON      => $offlinePayment->getUnexpectedReason(),
            ]
            );

            // Updates virtual account's stats.
            $this->virtualAccount->updateWithOfflinePayment($offlinePayment);

            $this->repo->saveOrFail($this->virtualAccount);

    }

    protected function createUnexpectedPayment(Entity $bankTransfer, array $gatewayData = [])
    {
        $this->virtualAccount = (new VirtualAccount\Core())->createOrFetchSharedVirtualAccount();

        $this->setMerchant();

        $bankTransfer->merchant()->associate($this->merchant);

        $bankTransfer->virtualAccount()->associate($this->virtualAccount);

        $input = $this->getPaymentArray($bankTransfer);

        $this->paymentProcessor = new Payment\Processor\Processor($this->merchant);

        return $this->createPayment($input, $gatewayData);
    }


    protected function createOfflinePayment(&$offlinePayment, array $input, array $gatewayData)
    {
        try
        {
            $this->getPaymentProcessor()->process($input, $gatewayData);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::INFO,
                TraceCode::VIRTUAL_ACCOUNT_FAILED_FOR_ORDER, ['input' => $input]);

            if ($e->getMessage() === PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH)
            {
                $this->pushVaPaymentFailedDueToOrderAmountMismatchEventToLake($input, $e);

                $offlinePayment->setUnexpectedReason(StatusCode::ORDER_AMOUNT_MISMATCH);
            }

            throw $e;
        }
    }

    protected function getVirtualAccountFromEntity(Base\PublicEntity $offline)
    {

        $challan_number = $offline->getChallanNumber();

        $challanRepo = new OfflineChallan\Repository;

        $offline_challan = $challanRepo->fetchByChallanNumber($challan_number);

        if ($offline_challan === null)
        {
            return null;
        }

        return $offline_challan->virtualAccount;
    }

    /**
     * Payment array use to send to Payment\Processor for bank transfer payments
     * Bank transfer description field may contain customer remarks, so use that.
     * If the VA has an associated customer, use those details as well.
     *
     * @param Base\PublicEntity $offlinePayment
     *
     * @return array
     */
    protected function getPaymentArray(Base\PublicEntity $offlinePayment): array
    {
        $parentPaymentArray = $this->getDefaultPaymentArray();

        $paymentArray = [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => Payment\Method::OFFLINE,
            Payment\Entity::AMOUNT      => $offlinePayment->getAmount(),
            Payment\Entity::DESCRIPTION => $offlinePayment->getDescription() ?? '',
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        if ($this->virtualAccount->hasOrder() === true)
        {
            $order = $this->virtualAccount->entity;

            $paymentArray[Payment\Entity::ORDER_ID] = $order->getPublicId();
        }

        $merchant = $this->virtualAccount->merchant;

        if ($merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $paymentArray[Payment\Entity::FEE] = (new Core)->getFeesForOffline($offlinePayment, $merchant);
        }

        return $paymentArray;
    }

    protected function getReceiver()
    {
        return $this->virtualAccount->offlineChallan;
    }



}
