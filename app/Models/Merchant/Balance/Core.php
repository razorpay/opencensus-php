<?php

namespace RZP\Models\Merchant\Balance;

use App;
use Mail;
use RZP\Models\Base;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Transaction;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Merchant\Credits;
use RZP\Models\Transaction\Processor\Refund as RefundTransactionProcessor;

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

    const RESERVE_FLOWS = [
        Type::PRIMARY => [
            Transaction\Type::PAYMENT,
            Transaction\Type::TRANSFER,
            Transaction\Type::REFUND,
        ],
        Type::BANKING => [],
        Type::COMMISSION => [],
    ];

    const NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS = [
        Payment\Method::EMANDATE,
        Payment\Method::NACH,
    ];


    // Merchant Onboarding for International Merchants wouldn't be happening via ledger
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

        // We need to associate the merchant before validation starts
        // Because in validate currency merchant entity is required
        $balance = (new Entity)->merchant()->associate($merchant);

        $balance = $balance->build($input);

        $balance->setConnection($mode);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * @param Base\Entity $merchant
     * @param array $input
     * @param string $mode
     * @param int $initialBalance
     * @return Entity
     */
    public function createWithInitialBalance(Base\Entity $merchant,
                                             array $input,
                                             string $mode,
                                             int $initialBalance): Entity
    {
        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_CREATE_REQUEST,
            [
                'input' => $input,
                'mode'  => $mode,
            ]
        );

        // We need to associate the merchant before validation starts
        // Because in validate currency merchant entity is required
        $balance = (new Entity)->merchant()->associate($merchant);

        $balance = $balance->build($input);

        $balance->setBalance($initialBalance);

        $balance->setConnection($mode);

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
                Entity::CURRENCY => $merchant->getCurrency(),
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
     * Fetches reserve balance for a merchant and create if does not exists
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
                Entity::MERCHANT_ID  => $merchant->getId()
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
                        Entity::MERCHANT_ID    => $merchant->getId()
                    ]
                );

                return [$balance, true];
            },
            self::RESERVE_BALANCE_CREATE_LOCK_TIMEOUT,
            ErrorCode::RESERVE_BALANCE_CREATE_ALREADY_IN_PROGRESS);
    }

    /**
     * Corpcard  balance is for capital corp card created on business banking
     * This is of account_type=corp_card, and only one of these can exist (currently)
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param string          $mode
     *
     * @return Entity
     */

    public function createBalanceForCorpCard(Merchant\Entity $merchant, array $input, string $mode)
    {
        $content = [
            Entity::TYPE     => Product::BANKING,
            Entity::CURRENCY => $merchant->getCurrency(),
        ];

        $input = array_merge($input, $content);

        $balance = $this->create($merchant, $input, $mode);

        return $balance;
    }

    public function createBalanceForCurrentAccount(Merchant\Entity $merchant, array $input, string $mode)
    {
        $content = [
            Entity::TYPE     => Product::BANKING,
            Entity::CURRENCY => $merchant->getCurrency(),
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
     * @param null $mode
     *
     * @return array
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
                Entity::CURRENCY     => $merchant->getCurrency(),
            ];

            $balance = $this->create($merchant, $input, $mode);

            return [$balance, true];
        }

        return [$balance, false];
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
     * @param int $amount
     * @param string $txnType
     * @param bool $negativeBalanceEnabled
     * @param string $balanceType
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

        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchant->getId(), RazorxTreatment::REFUND_CREDITS_WITH_LOCK, $mode);

        $this->trace->info(
            TraceCode::SCROOGE_FETCH_REFUND_CREDITS_WITH_LOCK,
            [
                'result' => $result,
                'mode' => $mode,
                'merchant_id' => $merchant->getId(),
            ]);

        if(strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $refundCredits = $this->repo->credits->getMerchantCreditsOfType($merchant->getId(), Credits\Type::REFUND);
        }
        else
        {
            $balance = $merchant->getBalanceByTypeOrFail($balanceType);

            $refundCredits = $balance->getRefundCredits();
        }

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

    /**
     * @param Merchant\Entity $merchant
     * @param string $balanceType
     * @param string $txnType
     * @return int
     * @throws BadRequestException
     */
    protected function getMaximumNegativeAllowedForBalanceType(Merchant\Entity $merchant,
                                                            string $balanceType,
                                                            string $txnType) : int
    {
        $balance = $merchant->getBalanceByTypeOrFail($balanceType);

        $reserveAmount = $this->getReserveAmount($merchant, $balanceType);

        if ($txnType === Transaction\Type::PAYMENT)
        {
            $maxNegative = (new BalanceConfig\Core)->getMaxNegativeAmountAutoForBalanceId($balance->getId());

            // If Txn Type is Payment, it is auto Negative Balance and it qualifies for Default Max Negative
            // if no Auto Max negative is explicitly set.
            if($maxNegative === 0)
            {
                return max($reserveAmount, BalanceConfig\Entity::DEFAULT_MAX_NEGATIVE);
            }

            return max($reserveAmount, $maxNegative);
        }

        $negativeAllowedFlows = (new BalanceConfig\Core)->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            if (in_array($txnType, self::RESERVE_FLOWS[$balanceType]) === false)
            {
                return 0;
            }
            else
            {
                return $reserveAmount;
            }
        }

        $maxNegative = (new BalanceConfig\Core)->getMaxNegativeAmountManualForBalanceId($balance->getId());

        $minimumNegativeAllowed =  max($reserveAmount, $maxNegative);

        return $minimumNegativeAllowed;
    }

    /**
     * Current Reserve balance in reserve_primary or reserve_banking balance types.
     *
     * @param Merchant\Entity $merchant
     * @param string $balanceType
     * @return int
     */
    private function getReserveAmount(Merchant\Entity $merchant, string $balanceType): int
    {
        $reserveBalance = null;

        if (($balanceType === Type::PRIMARY) or
            ($balanceType === Type::BANKING))
        {
            $reserveType = 'reserve_' . $balanceType;
        }
        else
        {
            return 0;
        }

        $reserveBalance = $merchant->getBalanceByType($reserveType);

        $reserveAmount = $reserveBalance !== null ? $reserveBalance->getBalance() : 0;

        return $reserveAmount;
    }

    /**
     * Get the Maximum Negative Limit upto which the balance can go negative
     *
     * @param Transaction\Entity $txn
     * @return int
     */
    public function getNegativeLimit(Transaction\Entity $txn) : int
    {
        $txnSource = $txn->source;

        /** @var Entity $balance */
        $balance = optional($txnSource->balance);

        $balanceType = $balance->getType();

        $accountType = $balance->getAccountType();

        // Not allowing negative balance in refund credits
        // Ref slack thread: https://razorpay.slack.com/archives/C6XG1F99N/p1651128045835069?thread_ts=1642673759.195000&cid=C6XG1F99N
        if ($txn->isRefundCredits() === true)
        {
            return 0;
        }

        if (($balanceType === Type::BANKING) and
            ($accountType === AccountType::DIRECT))
        {
            if ($balance->getChannel() === Channel::RBL)
            {
                return -1 * BalanceConfig\Entity::BANKING_MAX_NEGATIVE_FOR_RBL;
            }
            else if ($balance->getChannel() === Channel::ICICI)
            {
                return -1 * BalanceConfig\Entity::BANKING_MAX_NEGATIVE_FOR_ICICI;
            }
        }


        $negativeLimit = 0;

        $balanceType = $this->getTransactionBalanceType($txn);

        // If the Transaction Type is Payment, then we only allow Negative Balance for
        // E-Mandate/Nach Registrations
        if ($txn->getType() === Transaction\Type::PAYMENT)
        {
            if ($txn->source === null)
            {
                return $negativeLimit;
            }

            $payment = $txn->source;

            // Only allowed for E-Mandate/Nach Registerations: (Recurring type: Initital, not second recurring).
            if ((in_array($payment->getMethod() , self::NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS) === false) or
                ($payment->isRecurringTypeInitial() === false))
            {
                return $negativeLimit;
            }
        }

        $negativeBalanceEnabled = (new BalanceConfig\Core)->isNegativeBalanceEnabledForTxnAndMerchant($txn->getType(),
                                                                                                        $balanceType);

        if ($negativeBalanceEnabled === true)
        {
            return -1 * $this->getMaximumNegativeAllowedForBalanceType($txn->merchant, $balanceType, $txn->getType());
        }

        return $negativeLimit;
    }

    /**
     * Get the balance type of balance associated to this transaction source.
     *
     * @param Transaction\Entity $txn
     * @return string
     */
    public function getTransactionBalanceType(Transaction\Entity $txn) : string
    {
        $txnSource = $txn->source;

        $balanceType = Type::PRIMARY;

        if ($txnSource !== null)
        {
            $balance = $txnSource->balance;

            $balanceType = $balance !== null ? $balance->getType() : Type::PRIMARY;
        }

        return $balanceType;
    }

    /**
     * Some post steps for Negative Balance,
     * counting metrics and send negative balance alert mails.
     *
     * @param int $oldBalance: Balance/Refund Credits before getting updated.
     * @param string $balanceSource: Balance Source is the source of money selected for this transaction.
     * Can be Merchant Balance or Refund Credits.
     * @param string $txnType: This Transaction type
     * @param Entity $merchantBalance: Update Merchant Balance/Refund Credits
     */
    public function postProcessingForNegativeBalance(int $oldBalance,
                                                     string $balanceSource,
                                                     string $txnType,
                                                     Entity $merchantBalance)
    {
        $balanceType = $merchantBalance->getType();

        if ($balanceType !== Type::PRIMARY)
        {
            return;
        }

        $merchantId = $merchantBalance->merchant->getId();

        $negativeBalanceEnabled = (new BalanceConfig\Core)->isNegativeBalanceEnabledForTxnAndMerchant($txnType,
                                                                                                        $balanceType);

        if ($negativeBalanceEnabled === true)
        {
            if ($balanceSource === Entity::REFUND_CREDITS)
            {
                $newBalance = $merchantBalance->getRefundCredits();
            }
            else
            {
                $newBalance = $merchantBalance->getBalance();
            }

            if ($newBalance < 0)
            {
                $dimensions = (new Metric)->getBalanceNegativeDimensions($merchantId, $balanceType,
                                                                         $merchantBalance->getBalance(), $txnType);

                $this->trace->count(Metric::BALANCE_NEGATIVE, $dimensions);
            }

            $maxNegativeAllowed = $this->getMaximumNegativeAllowedForBalanceType($merchantBalance->merchant,
                                                                                    $balanceType, $txnType);

            (new NegativeReserveBalanceMailers)->sendNegativeBalanceMailIfApplicable($merchantBalance->merchant,
                $oldBalance, $newBalance, $maxNegativeAllowed, $balanceSource, $txnType);
        }
    }
}
