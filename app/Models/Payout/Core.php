<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Transaction;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundTransfer\Batch\BatchFundTransferTrait;

class Core extends Base\Core
{
    use BatchFundTransferTrait;

    const MUTEX_RESOURCE        = 'PAYOUT_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Create a direct payout - source from merchant balance
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return Payout\Entity
     */
    public function directPayout(array $input, Merchant\Entity $merchant): Entity
    {
        $payout = $this->createPayout($input, $merchant);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    /**
     * Create a payment payout - from a source payment
     *
     * @param  array           $input
     * @param  Payment\Entity  $payment
     * @param  Merchant\Entity $merchant
     * @return Payout\Entity
     */
    public function paymentPayout(array $input, Payment\Entity $payment, Merchant\Entity $merchant)
    {
        $payout = $this->createPayout($input, $merchant);

        $payout->payment()->associate($payment);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    /**
     * Initiate bank transfers for payouts
     *
     * @param  array  $input
     * @param  string $channel
     * @return array
     */
    public function initiatePayouts(array $input, string $channel): array
    {
        return $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function() use($input, $channel)
            {
                return $this->processBankPayouts($input, $channel);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * Called for cron or API to
     * create a payout for a merchant
     *
     * @param  array           $input
     * @return array
     */
    public function merchantPayout(array $input, Merchant\Entity $merchant): array
    {
        $merchantId = $merchant->getId();

        $bankAccountId = $input[Entity::DESTINATION_ID];

        $customerId = $input[Entity::CUSTOMER_ID];

        if (isset($input[Entity::AMOUNT]) === true)
        {
            $amount = $input[Entity::AMOUNT];
        }
        else
        {
            $amount = $merchant->balance->getBalance();
        }

        if ((isset($input[Entity::MIN_AMOUNT]) === true) and
            ($amount < $input[Entity::MIN_AMOUNT]))
        {
            $this->trace->info(
                TraceCode::MERCHANT_PAYOUT_FAILURE,
                [
                    'message'     => 'amount is less than min amount',
                    'merchant_id' => $merchantId,
                    'input'       => $input,
                ]);

            return ['message' =>
                'amount to be transferred is less than ' . $input[Entity::MIN_AMOUNT]];

        }

        // Modulo will convert the amount into multiples
        // of modulo value
        if (isset($input[Entity::MODULO]) === true)
        {
            $moduloAmount = $amount % $input[Entity::MODULO];

            $amount = $amount - $moduloAmount;
        }

        $payoutInput = [
            Entity::CUSTOMER_ID    => $customerId,
            Entity::AMOUNT         => $amount,
            Entity::CURRENCY       => 'INR',
            Entity::METHOD         => Method::FUND_TRANSFER,
            Entity::DESTINATION    => $bankAccountId,
        ];

        $payout = $this->directPayout($payoutInput, $merchant);

        return $payout->toArrayPublic();
    }

    protected function createPayout(array $input, Merchant\Entity $merchant): Entity
    {
        $this->validateMerchantStatus($merchant);

        return $this->repo->transaction(function () use ($input, $merchant)
        {
            $payout = $this->createPayoutEntity($input, $merchant);

            $payoutAttempt = $this->createPayoutAttemptEntity($payout);

            $this->updatePayoutWithTxn($payout);

            return $payout;
        });
    }

    protected function processBankPayouts(array $input, string $channel): array
    {
        return $this->repo->transaction(function() use ($input, $channel)
        {
            $timestamp = Carbon::now()->getTimestamp();

            $attempts = $this->repo
                             ->fund_transfer_attempt
                             ->getCreatedAttemptsBeforeTimestamp($timestamp, ['source']);

            $method = 'processBankPayoutsFor' . ucfirst($channel);

            // Calls $this->processBankPayoutsForKotak()
            $data[$channel] = $this->$method($attempts);

            return $data;
        });
    }

    protected function processBankPayoutsForKotak(Base\PublicCollection $payoutAttempts): array
    {
        $count = $payoutAttempts->count();

        $data = ['channel' => 'kotak', 'count' => $count];

        if ($count === 0)
        {
            $data['message'] = 'No payouts to process';

            return $data;
        }

        foreach ($payoutAttempts as $attempt)
        {
            // $attempt->source is payout entity
            $this->createOrUpdateBatchFundTransferForEntity($attempt->source, 1);

            $attempt->batchFundTransfer()->associate($this->batchFundTransfer);

            $attempt->setStatus(FundTransferAttempt\Status::INITIATED);

            $attempt->source->batchFundTransfer()->associate($this->batchFundTransfer);

            $attempt->source->setStatus(Status::INITIATED);
        }

        $urlText = (new Kotak\NodalAccount)->generatePayoutsFile($payoutAttempts);

        $urls = ['kotak_payout_txt'   => $urlText];

        $this->updateFileDetailsInBatchFundTransferEntity(['urls' => $urls]);

        $this->saveEntitiesToDb($payoutAttempts);

        $data['payout_text_file'] = $urlText;

        return $data;
    }

    protected function updatePayoutStatus(Base\PublicCollection $payouts, string $status)
    {
        $this->repo->payout->updateStatus($payouts, $status);
    }

    protected function saveEntitiesToDb(Base\PublicCollection $payoutAttempts)
    {
        foreach ($payoutAttempts as $attempt)
        {
            $this->repo->saveOrFail($attempt);

            $this->repo->saveOrFail($attempt->source);
        }
    }

    protected function createPayoutEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $payout = (new Entity)->build($input);

        $customer = $this->getCustomer($input, $merchant);

        $destination = $this->getPayoutDestination($input, $merchant, $customer);

        $payout->setChannel(Settlement\Channel::KOTAK);

        $payout->merchant()->associate($merchant);

        $payout->customer()->associate($customer);

        $payout->destination()->associate($destination);

        return $payout;
    }

    protected function createPayoutAttemptEntity(Entity $payout): FundTransferAttempt\Entity
    {
        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $values = [
            FundTransferAttempt\Entity::CHANNEL   => $payout->getChannel(),
            FundTransferAttempt\Entity::VERSION   => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS    => FundTransferAttempt\Status::CREATED,
            FundTransferAttempt\Entity::NARRATION => 'RAZORPAY SETTLEMENT',
        ];

        $fundTransferAttempt->fillAndGenerateId($values);

        $fundTransferAttempt->source()->associate($payout);

        $fundTransferAttempt->merchant()->associate($payout->merchant);

        $fundTransferAttempt->bankAccount()->associate($payout->destination);

        $this->repo->saveOrFail($fundTransferAttempt);

        return $fundTransferAttempt;
    }

    protected function getCustomer(array $input, Merchant\Entity $merchant): Customer\Entity
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $merchant);

        return $customer;
    }

    protected function getPayoutDestination(array $input, Merchant\Entity $merchant, Customer\Entity $customer)
    {
        $destId = $input[Entity::DESTINATION];

        if ($input[Entity::METHOD] === Method::FUND_TRANSFER)
        {
            $destination = $this->repo->bank_account->findByPublicIdAndMerchant($destId, $merchant);

            // Check if the bank account destination is linked to the customer
            if ($destination->getEntityId() !== $customer->getId())
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid destination_id: " . $destination->getPublicId());
            }
        }

        return $destination;
    }

    protected function updatePayoutWithTxn(Entity $payout)
    {
        $txnCore = new Transaction\Core;

        $txn = $txnCore->createFromPayout($payout);

        $payout->setFees($txn->getFee());

        $payout->setTax($txn->getTax());

        $this->validateMerchantBalance($payout);

        $txnCore->updateBalances($txn, true);

        $this->repo->saveOrFail($txn);
    }

    protected function validateMerchantBalance(Entity $payout)
    {
        $debitAmount = $payout->getAmount() + $payout->getFees();

        $hasBalance = (new Merchant\Balance\Core)
                           ->checkMerchantBalance($payout->merchant, $debitAmount);

        if ($hasBalance === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE);
        }
    }

    protected function validateMerchantStatus(Merchant\Entity $merchant)
    {
        $onHold = $merchant->getHoldFunds();

        if ($onHold === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD);
        }
    }
}
