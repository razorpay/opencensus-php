<?php

namespace RZP\Models\Merchant\Balance;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Transaction;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Mail\Merchant\NegativeBalanceAlert as NegativeBalanceAlertMail;
use RZP\Mail\Merchant\BalancePositiveAlert as BalancePositiveAlertMail;
use RZP\Mail\Merchant\ReserveBalanceActivate as ReserveBalanceActivateMail;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert as NegativeBalanceThresholdAlertMail;

class Core extends Base\Core
{
    const COMMISSION_BALANCE_CREATE_MUTEX_PREFIX = 'commission_balance_';
    const COMMISSION_BALANCE_CREATE_LOCK_TIMEOUT = 30; // seconds

    const RESERVE_BALANCE_CREATE_MUTEX_PREFIX = 'reserve_balance_';
    const RESERVE_BALANCE_CREATE_LOCK_TIMEOUT = 30;

    const NEGATIVE_FLOWS = [
        Type::PRIMARY => [
            Transaction\Type::PAYMENT,
            Transaction\Type::TRANSFER,
            Transaction\Type::REFUND,
            Transaction\Type::ADJUSTMENT,
        ],
        Type::BANKING => [
            Transaction\Type::PAYOUT,
            Transaction\Type::ADJUSTMENT,
        ],
        Type::COMMISSION => [
            Transaction\Type::ADJUSTMENT,
        ],
    ];

    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param string          $mode
     *
     * @return Entity
     */
    public function create(Merchant\Entity $merchant, array $input, string $mode): Entity
    {
        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_CREATE_REQUEST,
            [
                'input' => $input,
                'mode'  => $mode,
            ]
        );

        $balance = (new Entity)->build($input);

        $balance->setConnection($mode);

        $balance->merchant()->associate($merchant);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    public function updateBalanceAccountNumber(Entity $balance, string $accountNumber)
    {
        assertTrue($balance->getAccountNumber() === null, 'Attempting to re-update balance\'s account_number!');

        $balance->setAccountNumber($accountNumber);

        $this->repo->saveOrFail($balance);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string          $balanceType
     * @param null            $mode
     *
     * @return Entity
     */
    public function createOrFetchBalance(Merchant\Entity $merchant, string $balanceType, $mode = null): Entity
    {
        $balance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), $balanceType, $mode);

        if ($balance === null)
        {
            // Evey balance we create will start with 0 balance. if needed we can extend this.
            $input = [
                Entity::TYPE     => $balanceType,
                Entity::CURRENCY => Currency::INR,
            ];

            $balance = $this->create($merchant, $input, $mode);
        }

        return $balance;
    }


    /**
     * Fetches commission balance for a merchant and create if not exists
     *
     * @param Merchant\Entity $merchant
     * @param string          $mode
     *
     * @return Entity
     */
    public function createOrFetchCommissionBalance(Merchant\Entity $merchant, string $mode): Entity
    {
        $balance = $merchant->commissionBalance;

        if ($balance !== null)
        {
            return $balance;
        }

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = self::COMMISSION_BALANCE_CREATE_MUTEX_PREFIX. $merchant->getKey();

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchant, $mode)
            {
                return $this->createOrFetchBalance($merchant, Type::COMMISSION, $mode);
            },
            self::COMMISSION_BALANCE_CREATE_LOCK_TIMEOUT,
            ErrorCode::COMMISSION_BALANCE_CREATE_ALREADY_IN_PROGRESS);
    }

    /**
     * Fetches reserve balance for a merchant and create if not exists
     *
     * @param Merchant\Entity $merchant
     * @param Type $balanceType
     * @param string $mode
     *
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function createOrFetchReserveBalance(Merchant\Entity $merchant, string $balanceType, string $mode): array
    {
        $balance = $balanceType === Type::RESERVE_PRIMARY ? $merchant->reservePrimaryBalance :
                                                             $merchant->reserveBankingBalance;

        if ($balance !== null)
        {
            return [$balance, false];
        }

        $this->trace->info(TraceCode::RESERVE_BALANCE_CREATE_REQUEST,
            [
                Entity::TYPE         => $balanceType,
                Entity::MERCHANT_ID  => $merchant->getMerchantId()
            ]
        );

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = self::RESERVE_BALANCE_CREATE_MUTEX_PREFIX . $merchant->getKey();

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchant, $balanceType, $mode)
            {
                $balance = $this->createOrFetchBalance($merchant, $balanceType, $mode);

                $this->trace->info(TraceCode::RESERVE_BALANCE_CREATE_SUCCESSFUL,
                    [
                        Entity::TYPE           => $balanceType,
                        Entity::MERCHANT_ID    => $merchant->getMerchantId()
                    ]
                );

                return [$balance, true];
            },
            self::RESERVE_BALANCE_CREATE_LOCK_TIMEOUT,
            ErrorCode::RESERVE_BALANCE_CREATE_ALREADY_IN_PROGRESS);
    }

    public function createBalanceForCurrentAccount(Merchant\Entity $merchant, array $input, string $mode)
    {
        $content = [
            Entity::TYPE     => Product::BANKING,
            Entity::CURRENCY => Currency::INR,
        ];

        $input = array_merge($input, $content);

        $balance = $this->create($merchant, $input, $mode);

        return $balance;
    }

    /**
     * Shared Banking balance is the first banking account created on business banking
     * This is of account_type=shared, and only one of these can exist (currently)
     *
     * @param Merchant\Entity $merchant
     * @param null            $mode
     *
     * @return Entity
     */
    public function createOrFetchSharedBankingBalance(Merchant\Entity $merchant, $mode = null)
    {
        $balance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType(
                                            $merchant->getId(),
                                            Type::BANKING,
                                            AccountType::SHARED,
                                            $mode);

        if ($balance === null)
        {
            $input = [
                Entity::TYPE         => Type::BANKING,
                Entity::ACCOUNT_TYPE => AccountType::SHARED,
                Entity::CURRENCY     => Currency::INR,
            ];

            $balance = $this->create($merchant, $input, $mode);
        }

        return $balance;
    }

    /**
     * Check that a merchant's balance is greater than amount argument passed
     *
     * @param Merchant\Entity $merchant
     * @param int             $amount
     * @param string          $balanceType
     *
     * @return bool
     * @throws BadRequestException
     */
    public function checkMerchantBalance(
        Merchant\Entity $merchant,
        int $amount,
        string $txnType,
        bool $negativeBalanceEnabled = false,
        string $balanceType = Type::PRIMARY) : bool
    {
        $balance = $merchant->getBalanceByTypeOrFail($balanceType);

        $balanceAmount = $balance->getBalance();

        $this->trace->info(TraceCode::CHECK_MERCHANT_BALANCE,
            [
                'balance amount'   => $balanceAmount,
                'debit amount'     => $amount
            ]
        );

        if ($balanceAmount + $amount >= 0)
        {
            return true;
        }

        // If transaction is making balance increase then return true
        // (even if the final balance is still negative, there is slight increase)

        if ($balanceAmount + $amount > $balanceAmount)
        {
              return true;
        }

        $errorData = [
            'merchant_balance'  => $balanceAmount,
            'debit_amount'      => abs($amount)
        ];

        if (($negativeBalanceEnabled === false) and
            ($balanceAmount < abs($amount)))
        {
            $errorData['message'] = TraceCode::getMessage(TraceCode::MERCHANT_BALANCE_DEBIT_FAILURE);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE, abs($amount),
                $errorData);
        }

        // convert minimum negative to negative to compare summation of negative balance and debit amount
        $minimumNegativeAllowed = -1 * $this->getMaximumNegativeAllowedForBalanceType($merchant, $balanceType, $txnType);

        if ($minimumNegativeAllowed === 0)
        {
            $errorData['message'] = TraceCode::getMessage(TraceCode::MERCHANT_BALANCE_DEBIT_FAILURE);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE, abs($amount),
                $errorData);
        }

        if ($balanceAmount + $amount < $minimumNegativeAllowed)
        {
            $errorData['negative_limit'] = $minimumNegativeAllowed;
            $errorData['message'] = TraceCode::getMessage(TraceCode::NEGATIVE_BALANCE_BREACHED);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED, abs($amount),
                $errorData);
        }

        return true;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param int             $amount
     * @param string          $txnType
     * @param string          $balanceType
     * @return bool
     * @throws BadRequestException
     */
    public function checkMerchantRefundCredits(
        Merchant\Entity $merchant,
        int $amount,
        string $txnType,
        bool $negativeBalanceEnabled = false,
        string $balanceType = Type::PRIMARY) : bool
    {
        $balance = $merchant->getBalanceByTypeOrFail($balanceType);

        $refundCredits = $balance->getRefundCredits();

        $this->trace->info(TraceCode::CHECK_MERCHANT_BALANCE,
            [
                'refund credits'   => $refundCredits,
                'debit amount'     => $amount
            ]
        );

        if ($refundCredits + $amount >= 0)
        {
            return true;
        }

        $errorData = [
            'merchant_refund_credits'   => $refundCredits,
            'debit_amount'               => $amount
        ];

        if (($negativeBalanceEnabled === false) and
            ($refundCredits < abs($amount)))
        {
            $errorData['message'] = TraceCode::getMessage(TraceCode::MERCHANT_REFUND_CREDITS_DEBIT_FAILURE);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS, abs($amount),
                $errorData);
        }

        // convert minimum negative to negative to compare summation of negative balance and debit amount
        $minimumNegativeAllowed = -1 * $this->getMaximumNegativeAllowedForBalanceType($merchant, $balanceType, $txnType);

        if ($minimumNegativeAllowed === 0)
        {
            $errorData['message'] = TraceCode::getMessage(TraceCode::MERCHANT_REFUND_CREDITS_DEBIT_FAILURE);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS, abs($amount),
                $errorData);
        }

        if ($refundCredits + $amount < $minimumNegativeAllowed)
        {
            $errorData['message'] = TraceCode::getMessage(TraceCode::NEGATIVE_BALANCE_BREACHED);
            $errorData['negative_limit'] = $minimumNegativeAllowed;

            throw new BadRequestException(ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED, abs($amount),
                $errorData);
        }

        return true;
    }

    public function getMaximumNegativeAllowedForBalanceType(Merchant\Entity $merchant,
                                                            string $balanceType,
                                                            string $txnType) : int
    {
        $balance = $merchant->getBalanceByTypeOrFail($balanceType);

        $reserveAmount = $this->getReserveAmount($merchant, $balanceType);

        if ($txnType === Transaction\Type::PAYMENT)
        {
            $maxNegative = (new BalanceConfig\Core)->getMaxNegativeAmountAutoForBalanceId($balance->getId());

            if($maxNegative === 0)
            {
                return BalanceConfig\Entity::DEFAULT_MAX_NEGATIVE;
            }

            return max($reserveAmount, $maxNegative);
        }

        $negativeAllowedFlows = (new BalanceConfig\Core)->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            return 0;
        }

        $maxNegative = (new BalanceConfig\Core)->getMaxNegativeAmountManualForBalanceId($balance->getId());

        $minimumNegativeAllowed =  max($reserveAmount, $maxNegative);

        return $minimumNegativeAllowed;
    }

    private function getReserveAmount(Merchant\Entity $merchant, string $balanceType): int
    {
        $reserveBalance = null;

        $reserveType = 'reserve_' . $balanceType;

        try
        {
            $reserveBalance = $merchant->getBalanceByTypeOrFail($reserveType);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::DEBUG);
        }

        $reserveAmount = $reserveBalance !== null ? $reserveBalance->getBalance() : 0;

        return $reserveAmount;
    }

    public function sendNegativeBalanceMailIfApplicable(Merchant\Entity $merchant,
                                                        int $oldBalance,
                                                        int $newBalance,
                                                        string $balanceType,
                                                        string $balanceSource,
                                                        string $txnType)
    {
        if (($oldBalance < 0) and
            ($newBalance >= 0))
        {
            $this->sendMailBalanceBecamePositive($merchant, $newBalance, $balanceSource);
        }
        else
        {
            if (($newBalance >= 0) or
                ($oldBalance < $newBalance))
            {
                return;
            }

            $maxNegativeAllowed = $this->getMaximumNegativeAllowedForBalanceType($merchant, $balanceType, $txnType);

            if ($maxNegativeAllowed === 0)
            {
                return;
            }

            //percentage thresholds below which negative balance threshold breached mail should be sent
            $negativeThresholdAlerts = [50, 70, 80, 90, 80, 100];

            $oldPercentage = (int) abs(($oldBalance * 100) / $maxNegativeAllowed);

            $newPercentage = (int) abs(($newBalance * 100) / $maxNegativeAllowed);

            $thresholdBreached = false;

            foreach ($negativeThresholdAlerts as $threshold)
            {
                if (($newPercentage >= $threshold) and
                    ($oldPercentage < $threshold))
                {
                    $this->sendMailNegativeBalanceThresholdBreached($merchant, $newBalance, $newPercentage,
                                                                    $maxNegativeAllowed, $balanceSource, $txnType);

                    $thresholdBreached = true;

                    break;
                }
            }

            if (($thresholdBreached === false) and
                ($oldBalance >= 0) and
                ($newBalance < 0))
            {
                $this->sendMailBalanceBecameNegative($merchant, $newBalance, $balanceSource);
            }
        }
    }

    private function sendMailNegativeBalanceThresholdBreached(Merchant\Entity $merchant,
                                                              int $balance,
                                                              int $threshold,
                                                              int $maxNegativeAllowed,
                                                              string $balanceSource,
                                                              string $txnType)
    {
        $data = [
            'email'                  => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'percentage'            => $threshold,
            'max_negative_allowed'  => ($maxNegativeAllowed / 100) . " INR",
            'balance_source'        => $balanceSource,
            'balance'                => ($balance / 100) . " INR",
            'headers'                => MailTags::NEGATIVE_BALANCE_THRESHOLD_ALERT,
        ];

        $this->trace->info(TraceCode::NEGATIVE_BALANCE_THRESHOLD_ALERT, $data);

        $dimensions = (new Metric)->getBalanceNegativeThresholdBreachedDimensions($merchant, $balance, $threshold,
                                                                                    $txnType);

        $this->trace->count(Metric::BALANCE_NEGATIVE_THRESHOLD, $dimensions);

        $negativeBalanceAlertMail = new NegativeBalanceThresholdAlertMail($data);

        Mail::queue($negativeBalanceAlertMail);
    }

    private function sendMailBalanceBecameNegative(Merchant\Entity $merchant,
                                                   int $balance,
                                                   string $balanceSource)
    {
        $data = [
            'email'                  => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'balance'               => ($balance) / 100 . " INR",
            'balance_source'        => $balanceSource,
            'headers'                => MailTags::BALANCE_NEGATIVE_ALERT,
        ];

        $negativeBalanceAlertMail = new NegativeBalanceAlertMail($data);

        Mail::queue($negativeBalanceAlertMail);
    }

    private function sendMailBalanceBecamePositive(Merchant\Entity $merchant,
                                                   int $newBalance,
                                                   string $balanceSource)
    {
        $data = [
            'email'                  => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'balance'               => ($newBalance) / 100 . " INR",
            'balance_source'        => $balanceSource,
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'headers'                => MailTags::BALANCE_POSITIVE_ALERT,
        ];

        $balancePositiveAlertMail = new BalancePositiveAlertMail($data);

        Mail::queue($balancePositiveAlertMail);
    }

    public function sendReserveBalanceActivatedMail(Merchant\Entity $merchant, Entity $balance)
    {
        $data = [
            'email'                 => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'reserve_limit'         => ($balance->getBalance()) / 100 . " INR",
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'headers'               => MailTags::RESERVE_BALANCE_ACTIVATED,
        ];

        $reserveBalanceActivateMail = new ReserveBalanceActivateMail($data);

        Mail::queue($reserveBalanceActivateMail);
    }

    public function getNegativeLimit(Transaction\Entity $txn)
    {
        $negativeBalanceEnabled =  (new BalanceConfig\Core)->isNegativeBalanceEnabledForTxnAndMerchant($txn->getType(),
                                                                                            $txn->merchant->getId());

        $negativeLimit = 0;

        if ($negativeBalanceEnabled === true)
        {
            $txnSource = $txn->source;

            $balanceType = Type::PRIMARY;

            if ($txnSource !== null)
            {
                $balance = $txnSource->balance;

                $balanceType = $balance !== null ? $balance->getType() : Type::PRIMARY;
            }

            $negativeLimit = -1 * $this->getMaximumNegativeAllowedForBalanceType($txn->merchant, $balanceType, $txn->getType());
        }

        return $negativeLimit;
    }
}
