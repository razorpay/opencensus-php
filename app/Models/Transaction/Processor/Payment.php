<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Credits;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base as BaseCollection;
use RZP\Models\Payment as PaymentEntity;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Partner\Service as PartnerService;
use RZP\Models\Schedule\Library as ScheduleLibrary;
use RZP\Models\EntityOrigin\Constants as EntityOriginConstants;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantAppEntity;

class Payment extends Base
{
    public function updateTransaction()
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_CREATE_TRANSACTION,
            [
                'payment_id'            => $this->source->getId(),
                'transaction_id'        => $this->txn->getId(),
                'transaction_amount'    => $this->txn->getAmount(),
                'transaction_credit'    => $this->txn->getCredit(),
                'transaction_debit'     => $this->txn->getDebit(),
                'transaction_fees'      => $this->txn->getFee()
            ]);

        $this->checkAndSetTxnReconciliation();

        $this->setTransactionOnHoldIfApplicable();

        $this->repo->saveOrFail($this->txn);

        if (($this->txn->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === false) or
            $this->source->isExternal() === true)
        {
            $this->fillSettledAtInfo();
        }
    }

    public function getValuesForCustomerFeeAndTaxInTransaction($payment): array
    {
        if ($payment->hasOrder() === true and
            $payment->order->getFeeConfigId() !== null)
        {
            $fee = $this->txn->getFee();

            $tax = $this->txn->getTax();

            $rzpFee = $fee - $tax;

            $customerFee = (new paymentEntity\processor\processor($this->merchant))->calculateCustomerFee($payment, $payment->order, $rzpFee);

            $customerFeeGst = (new paymentEntity\processor\processor($this->merchant))->calculateCustomerFeeGst($customerFee, $rzpFee, $tax);

            return [
                Transaction\Entity::CUSTOMER_FEE => $customerFee,
                Transaction\Entity::CUSTOMER_TAX => $customerFeeGst,
                Transaction\Entity::FEE => $fee - ($customerFee + $customerFeeGst),
                Transaction\Entity::TAX => $tax - $customerFeeGst,
                Transaction\Entity::DEBIT => $customerFee + $customerFeeGst,
            ];
        }
        return [];
    }

    public function fillSettledAtInfo()
    {
        $settledAt = $this->getSettledAtTimestamp();

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->raiseTxnSettledAtUpdateEvent($this->txn);
    }

    private function raiseTxnSettledAtUpdateEvent(Transaction\Entity $txn)
    {
        $type = $this->txn->getType();

        $entity_id = $this->txn->getEntityId();

        $channel = $this->txn->getChannel();

        $transactionId = $this->txn->getId();

        $merchantId = $this->txn->getMerchantId();

        $customProperties = [
            'type'              => $type,
            'entity_id'         => $entity_id,
            'channel'           => $channel,
            'transaction_id'    => $transactionId,
            'merchant_id'       => $merchantId,
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::TRANSACTION_SETTLED_AT_UPDATE,
            null,
            null,
            $customProperties);
    }

    private function checkAndSetTxnReconciliation()
    {
        if ($this->source->getGateway() === Gateway::WALLET_OPENWALLET)
        {
            $this->txn->setReconciledAt(time());

            $this->txn->setReconciledType(ReconciledType::NA);
        }
    }

    public function createTransaction($txnId = null)
    {
        //
        // We have the check on captured_at because of the following reason:
        // - A payment happens on an auth&capture supported gateway. This means
        //   that the transaction will get created on `capture` in the normal flow.
        // - Capture completes and then API/DB goes down, due to which the transaction
        //   is not created. Also, payment is not marked as `captured`.
        // - This payment is refunded.
        // - Now we find out that this payment was actually successful from the bank side and that the capture is
        //   complete from the bank side. So we try to create a nodal transaction for this (not merchant transaction
        //   since it's not captured by the merchant). Note that the payment status is `refunded` currently.
        // - When we call `createTransaction`, the payment can either be ONLY gateway_captured or merchant_captured.
        //   If it's NOT merchant_captured, we create a nodal transaction. If it's merchant_captured, we create
        //   merchant transaction + nodal transaction (taken care by the parent::createTransaction).
        // - NOTE: `merchant_captured` is signified by `captured_at` value being set or not.
        //

        if ($this->source->hasBeenCaptured() === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_AUTHORIZE_CREATE_TRANSACTION,
                [
                    'payment_id' => $this->source->getId()
                ]);

            // Creates new or fetches existing transaction entity for the source entity
            $this->setTransactionForSource($txnId);

            // set transaction attributes from the source entity
            $this->setSourceDefaults();

            return $this->fillEmptyTxnFeesAndAmount();
        }

        return parent::createTransaction($txnId);
    }

    protected function shouldUpdateBalance()
    {
        //
        // ======================================================================================
        // NOTE: DO READ THIS COMPLETELY AND UNDERSTAND WHAT IS BEING DONE BEFORE MAKING CHANGES!
        // ======================================================================================
        //
        // In some flows, we would have taken a transaction and closed it after a lot
        // of things. This causes an issue since in this flow, we take a lock on balance
        // and we don't release the lock until the transaction is complete. So, we might
        // want to take the lock on balance and do the necessary updates during the
        // transaction closure time instead of doing it here.
        //
        //
        // When we are doing this, ensure that the source handles `shouldUpdateBalance` also.
        // In case of payments, we are setting this flag only on capture. Hence, not an issue.
        // Needs to be done source-by-source basis (see what I did there?)
        //
        // Any changes being done in this block, we should do it in the other places also
        // where we are updating the balance at the end of the transaction.
        //

        //
        // If authorized is false, it means that the payment is captured since
        // we create payment transaction only in two flows - authorized and captured.
        // If it's captured, we decide whether to do lateBalanceUpdate or not based
        // on some conditions.
        //
        if ($this->source->isAuthorized() === true)
        {
            // in authorize transaction we set to balance updated as true since there is no actual balance update.
            $this->txn->setBalanceUpdated(true);

            return false;
        }
        else if ($this->source->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $this->txn->setBalanceUpdated(true);
            return true;
        }
        else if (($this->source->merchant->isFeatureEnabled(Feature\Constants::ASYNC_BALANCE_UPDATE) === true) and
            ($this->source->isExternal() === false))
        {
            return false;
        }
        else if ($this->source->isLateBalanceUpdate() === true)
        {
            // in late balance update we do it on the fly and setting it to true for backward compatibility
            $this->txn->setBalanceUpdated(true);

            return false;
        }


        $this->txn->setBalanceUpdated(true);

        return true;

    }

    protected function shouldMoveTxnFillToAsync(): bool
    {
        if($this->source->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            return false;
        }

        return (($this->source->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === true) and
            ($this->source->isExternal() === false));
    }

    protected function fillEmptyTxnFeesAndAmount()
    {
        $amount = $this->source->getBaseAmount();

        $values = [
            Transaction\Entity::DEBIT               => 0,
            Transaction\Entity::CREDIT              => 0,
            Transaction\Entity::FEE                 => 0,
            Transaction\Entity::TAX                 => 0,
            Transaction\Entity::AMOUNT              => $amount,
        ];

        $this->txn->fill($values);

        return [$this->txn, new BaseCollection\PublicCollection];

    }

    protected function setTransactionForSource($txnId = null)
    {
        if ($this->source->isExternal() === true)
        {
            $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($this->source);

            if ($txn !== null)
            {
                $this->setTransaction($txn);

                return;
            }
        }

        parent::setTransactionForSource($txnId);
    }

    public function fillDetails()
    {
        parent::fillDetails();

        $this->txn->setFeeBearer($this->source->getFeeBearer());
    }


    protected function setMerchantCredits()
    {
        // TODO: There's no lock being taken here for balance!

        $this->setMerchantBalance();

        $merchant = $this->merchantBalance->merchant;

        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            $amountCredits = $this->merchantBalance->getAmountCredits();

            $feeCredits = $this->merchantBalance->getFeeCredits();
        }
        else
        {
            $credits = $this->repo->credits->getTypeAggregatedMerchantCreditsForPayment($this->merchantBalance->merchant);

            $amountCredits =  $credits[Credits\Type::AMOUNT] ?? 0;

            $feeCredits = $credits[Credits\Type::FEE] ?? 0;
        }

        $this->amountCredits = $amountCredits;
        $this->feeCredits = $feeCredits;
    }

    public function calculateFees()
    {
        switch (true)
        {
            case ($this->isVasMerchantWithDirectSettlement()):
            case ($this->txn->isFeeBearerCustomer()):
                $this->calculateFeeDefault();
                break;

            // @todo: Need to rethink this.
            case (($this->amountCredits > 0) and ($this->source->getAmount() !== 0) and ($this->shouldDisableAmountCredits()=== false)
                and ($this->amountCredits >= $this->txn->getAmount())):
                $this->calculateFeeForAmountCredit();
                break;

            case (($this->feeCredits > 0) and ($this->feeCredits >= $this->fees)):
                $this->calculateFeeForFeeCredit();
                break;

            default:
                $this->calculateFeeDefault();
        }

        $amount = $this->getNetAmount();

        $this->credit = 0;
        $this->debit  = 0;

        if ($amount > 0)
        {
            $this->credit = $amount;
        }
        else
        {
            $this->debit = -1 * $amount;
        }

        $this->trace->debug(TraceCode::CALCULATED_FEES_FOR_PAYMENT,
            [
                'credit'            => $this->credit,
                'debit'             => $this->debit,
                'amount'            => $amount,
                'fee'               => $this->fees,
                'fee_credits'       => $this->feeCredits,
                'amount_credits'    => $this->amountCredits
            ]
        );

    }

    public function shouldDisableAmountCredits():bool
    {
        $payment = $this->source;

        $merchant = $payment->merchant;

        $merchantDetail = $merchant->merchantDetail;

        if (isset($merchantDetail) === false)
        {
            return false;
        }

        if (($merchantDetail->isUnregisteredBusiness() === true)
            and (new Merchant\Core())->isDisableFreeCreditsFeatureEnabled($merchant, Feature\Constants::DISABLE_FREE_CREDIT_UNREG) === true)
        {
                return $this->isMethodCreditCard();
        }
        else if (($merchantDetail->isUnregisteredBusiness() === false)
            and (new Merchant\Core())->isDisableFreeCreditsFeatureEnabled($merchant, Feature\Constants::DISABLE_FREE_CREDIT_REG) === true)
        {
            return $this->isMethodCreditCard();
        }

        return false;
    }

    public function isMethodCreditCard() : bool
    {
        $payment = $this->source;

        if ($payment->isMethodCardOrEmi() === false)
        {
            return false;
        }

        $card = $payment->card;

        if (isset($card) === true and $card->isCredit() === true)
        {
            return true;
        }

        return false;
    }

    private function isVasMerchantWithDirectSettlement(): bool
    {
        $payment = $this->source;

        if ($payment->isDirectSettlement() === true)
        {
            $merchant = $payment->merchant;

            $isVasMerchant = $merchant->isFeatureEnabled(Feature\Constants::VAS_MERCHANT);

            if ($isVasMerchant === true)
            {
                return true;
            }
        }

        return false;
    }

    public function getNetAmount()
    {
        $amount = $this->txn->getAmount();

        $this->trace->debug(TraceCode::IS_DIRECT_SETTLEMENT_PAYMENT,
            [
                'is_direct_settlement' => $this->source->isDirectSettlement(),
            ]
        );


        if ($this->source->isDirectSettlement() === true)
        {
            $amount = 0;
        }

        $netAmount = 0;

        switch (true)
        {
            case ($this->source->isHdfcVasDSCustomerFeeBearerSurcharge()):
            case ($this->source->isHdfcNonDSSurcharge()):
            case ($this->isVasMerchantWithDirectSettlement()):
            case ($this->txn->isPostpaid() === true):
            case ($this->txn->getCreditType() === Transaction\CreditType::FEE):
            case (($this->source->isCardlessEmiWalnut369()) and ($this->source->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true)):
            case ($this->txn->getCreditType() === Transaction\CreditType::AMOUNT):
                $netAmount = $amount;
                break;

            default:
                $netAmount = $amount - $this->fees;
        }
        // READ THIS TO UNDERSTAND THE CRED DISCOUNT LOGIC
        // Transaction for cred case is created after payment is captured.
        // We receive discount data in callback step.
        // Net amount for a transaction is the amount after fees calculation(which is based on Pricing module logic).
        // For Cred, the coin discount will be subtracted from the base amount directly.
        // THe benefit here is that, the pricing of total charge of amount(P1 * 0.05), does not include coins. So RZP, Cred etc wont bear coins tax
        // On the other hand, if we set a fixed pricing(P1 * 0.15), the possible coin spent by customer is also taxed as per pricing.
        $payment = $this->source;
        $discountAmount = $this->getDiscountIfApplicable($payment);

        $netAmount -= $discountAmount;

        $this->trace->debug(TraceCode::NET_AMOUNT_FOR_TRANSACTION,
            [
                'credit'        => $this->credit,
                'debit'         => $this->debit,
                'amount'        => $amount,
                'discount'      => $discountAmount,
                'fee'           => $this->fees,
                'net_amount'    => $netAmount,
            ]
        );

        return $netAmount;
    }

    /**
     * Put payments transaction on hold by default for those sub-merchants whose partner have the feature
     * "subm_manual_settlement" enabled. For this to happen, the payment should be made via partner auth or OAuth.
     * TODO: remove the try-catch block when no errors are reported
     */
    public static function shouldHoldSubmerchantPayment(PaymentEntity\Entity $payment, Merchant\Entity $merchant): bool
    {
        try
        {
            $paymentOrigin = $payment->entityOrigin;

            $origin = optional($paymentOrigin)->origin;

            $originType = optional($origin)->getEntityName();

            if ($originType !== EntityOriginConstants::APPLICATION)
            {
                return false;
            }

            $originId = optional($origin)->getId();

            /* @var Merchant\Entity */
            $partner = (new Merchant\Core())->getPartnerFromApp($origin);

            $appType = (new Merchant\MerchantApplications\Core())->getDefaultAppTypeForPartner($partner);

            if (empty($partner) === true or
                in_array($partner->getPartnerType(), [Merchant\Constants::AGGREGATOR, Merchant\Constants::PURE_PLATFORM]) === false or
                (new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::SUBM_MANUAL_SETTLEMENT, $partner, $originId) === false or
                (new Merchant\AccessMap\Core())->isMerchantMappedToPartnerWithAppType($partner, $merchant, $appType) === false)
            {
                return false;
            }

            app('trace')->info(
                TraceCode::PUT_SUBMERCHANT_PAYMENT_ON_HOLD,
                [
                    Merchant\Constants::PARTNER_ID  => $partner->getId(),
                    Merchant\Constants::MERCHANT_ID => $merchant->getId(),
                    Transaction\Entity::PAYMENT_ID  => $payment->getId()
                ]
            );

            return true;
        }
        catch (\Throwable $ex)
        {
            app('trace')->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PUT_SUBMERCHANT_PAYMENT_ON_HOLD_FAILED,
                [
                    'payment_id' => empty($payment) === false ? $payment->getId() : null
                ]
            );
        }

        return false;
    }

    protected function getSettledAtTimestamp()
    {
        $payment = $this->source;

        $capturedAt = $payment->getAttribute(PaymentEntity\Entity::CAPTURED_AT);

        $merchant = $payment->merchant;

        $scheduleTask = (new ScheduleTask\Core)->getMerchantSettlementSchedule(
            $merchant,
            $payment->getMethod(),
            $payment->isInternational());

        // use schedule from pivot schedule_task if defined and use next run from there
        if ($scheduleTask !== null)
        {
            $schedule = $scheduleTask->schedule;

            $nextRunAt = $scheduleTask->getNextRunAt();

            $returnTime = ScheduleLibrary::getNextApplicableTime($capturedAt, $schedule, $nextRunAt);
        }
        else
        {
            $addDays = $payment->isInternational() === true ?
                        Merchant\Entity::INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY :
                        Merchant\Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

            $returnTime = $this->calculateSettledAtTimestamp($capturedAt, $addDays);
        }

        return $returnTime;
    }

    protected function setTransactionOnHoldIfApplicable()
    {
        $payment = $this->source;

        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::TRANSACTION_ON_HOLD) === true or
            $merchant->isOpgspImportEnabled() === true or
            self::shouldHoldSubmerchantPayment($payment, $merchant) === true)
        {
            $this->txn->setOnHold(true);
        }
    }

    protected function getDiscountIfApplicable($payment)
    {
        if ($payment->isAppCred() === true)
        {
            $discount = $this->repo->discount->fetchForPayment($payment);

            if ($discount !== null)
            {
                return $discount->getAmount();
            }
        }

        /* For the walnut369 sourced merchant, we don't apply our pricing on the payment and instead settle the amount
         * based on the subvention/mdr received in the payment which is used to create discount entity
         * */
        if (($payment->isCardlessEmiWalnut369() === true) and ($payment->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true))
        {
            $discount = $this->repo->discount->fetchForPayment($payment);

            if ($discount !== null)
            {
                $this->fees = 0;
                $this->tax = 0;
                return $discount->getAmount();
            }
        }

        return 0;
    }
}
