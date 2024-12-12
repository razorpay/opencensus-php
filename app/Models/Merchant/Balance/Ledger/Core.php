<?php

namespace RZP\Models\Merchant\Balance\Ledger;

use App;

use Ramsey\Uuid\Uuid;
use RZP\Constants\Metric;
use RZP\Models\Base;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Merchant\Credits;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\ConnectionType;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Credits\Type;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\BankingAccount\Entity as BankingAccount;
use RZP\Models\Merchant\Credits\Balance\Entity as CreditEntity;
use RZP\Models\BankingAccountStatement\Details\Entity as BankingAccountStatementDetails;

class Core extends Base\Core
{
    const LEDGER_ACCOUNT_ONBOARDING             = 'ledger_account_onboarding';
    const DIRECT_MERCHANT_ONBOARDING            = 'direct_merchant_onboarding';
    const SHARED_MERCHANT_ONBOARDING            = 'shared_merchant_onboarding';
    const SHARED_GATEWAY_ONBOARDING             = 'shared_gateway_onboarding';
    const PG_MERCHANT_ONBOARDING                = 'pg_merchant_onboarding';
    const PG_GATEWAY_ONBOARDING                 = 'pg_gateway_onboarding';
    const PG_MERCHANT_CREDIT_ONBOARDING         = 'pg_merchant_credit_onboarding';
    const LEDGER_ONDEMAND_SETTLEMENT_ONBOARDING = "capital_odsettlement_onboarding";

    const MODE                              = 'mode';
    const TENANT                            = 'tenant';
    const MERCHANT_ID                       = 'merchant_id';
    const EVENT                             = 'event';
    const EVENTS                            = 'events';
    const EVENT_NAME                        = 'name';
    const EVENT_DESCRIPTION                 = 'description';
    const ENTITIES                          = 'entities';
    const FTS_FUND_ACCOUNT_ID               = 'fts_fund_account_id';
    const BANKING_ACCOUNT_ID                = 'banking_account_id';
    const BANKING_ACCOUNT_STMT_DETAILS_ID   = 'banking_account_stmt_detail_id';
    const ACCOUNT_TYPE                      = 'account_type';
    const FUND_ACCOUNT_TYPE                 = 'fund_account_type';
    const PAYABLE                           = 'payable';
    const MERCHANT_DA                       = 'merchant_da';
    const BALANCE                           = 'balance';
    const MIN_BALANCE                       = 'min_balance';
    const FEE                               = 'fee';
    const AMOUNT                            = 'amount';
    const REFUND                            = 'refund';
    const GATEWAY                           = 'gateway';
    const MERCHANT_FEE_CREDITS              = 'merchant_fee_credits';
    const MERCHANT_AMOUNT_CREDITS           = 'merchant_amount_credits';
    const REWARD                            = 'reward';
    const MERCHANT_REFUND_CREDITS           = 'merchant_refund_credits';
    const PAYLOAD                           = 'payload';
    const LEDGER_RESPONSE                   = 'LEDGER_RESPONSE';
    const NOT_UPDATED                       = "not updated";
    const MERCHANT_BALANCE_OPENING_BALANCE   = 'merchant_balance_opening_balance';
    const MERCHANT_REWARD_OPENING_BALANCE    = 'merchant_reward_opening_balance';
    const MERCHANT_FEE_OPENING_BALANCE       = "merchant_fee_opening_balance";
    const MERCHANT_REFUND_OPENING_BALANCE    = "merchant_refund_opening_balance";
    const MERCHANT_RESERVE_OPENING_BALANCE   = "merchant_reserve_opening_balance";
    const MERCHANT_BALANCE_MINIMUM_BALANCE   = "merchant_balance_minimum_balance";
    const MERCHANT_OPENING_BALANCES          = "merchant_opening_balances";

    const CREDIT_ID  = 'credit_id';
    const EXPIRED_AT = 'expired_at';

    const IDEMPOTENCY_KEY = 'idempotency_key';
    const UUID_FORMAT     = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';

    const X = 'X';
    const PG = 'PG';

    const SHARED = 'shared';
    const DIRECT = 'direct';

    // Ledger sync retry
    const DEFAULT_MAX_RETRY_COUNT = 3;

    protected $eventDescription = [
        self::DIRECT_MERCHANT_ONBOARDING    => 'Event for onboarding of merchant on direct account',
        self::SHARED_MERCHANT_ONBOARDING    => 'Event for onboarding of merchant on shared account',
        self::SHARED_GATEWAY_ONBOARDING     => 'Event for onboarding a gateway',
        self::PG_MERCHANT_ONBOARDING        => 'PG merchant onboarded',
        self::PG_GATEWAY_ONBOARDING         => 'PG gateway onboarded'
    ];

    const TIME_TAKEN       = 'time_taken';
    const REWARD_BALANCE   = 'reward_balance';
    const MERCHANT_BALANCE = 'merchant_balance';
    const MERCHANT_RESERVE_BALANCE = 'merchant_reserve_balance';

    /** @var LedgerService $ledgerService */
    protected $ledgerService;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->ledgerService = $this->app['ledger'];
    }

    /***
     * Push event to sns topic which will be consumed by ledger SQS to create accounts based on event
     * @param Merchant $merchant
     * @param BankingAccount $bankingAccount
     * @param string $mode
     * @param string $accountType can be shared (for Virtual Accounts) or direct (for Current Accounts)
     * @param int $balanceAmount
     * @param int $creditBalance
     * @param bool $isReverseShadow
     */
    public function createXLedgerAccount(Merchant $merchant, BankingAccount $bankingAccount,
                                         string   $mode, string $accountType = self::SHARED,
                                         int $balanceAmount = 0, int $creditBalance = 0, bool $isReverseShadow = false)
    {
        $event = $accountType == self::SHARED ? self::SHARED_MERCHANT_ONBOARDING : self::DIRECT_MERCHANT_ONBOARDING;

        $snsPyload = $this->getLedgerAccountCreateSNSPayload($mode, $merchant, $event, self::BANKING_ACCOUNT_ID, $bankingAccount->getPublicId(), $balanceAmount, $creditBalance, $bankingAccount->getFtsFundAccountId());
        if ($isReverseShadow === false)
        {
            // onboard to ledger in async for shadow mode
            $this->createXLedgerAccountPushToSNS($snsPyload);
            return;
        }

        try
        {
            // onboard to ledger in sync for reverse shadow mode
            $payload = $this->getLedgerAccountCreatePayload($mode, $merchant, $event, self::BANKING_ACCOUNT_ID, $bankingAccount->getPublicId(), $balanceAmount, $creditBalance, $bankingAccount->getFtsFundAccountId());
            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER    => self::X,
                LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
            ];
            $ledgerService = $this->app['ledger'];
            $ledgerService->createAccountsOnEvent($payload, $requestHeaders, true);
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception and retry in async
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_CREATE_REQUEST_FAILED,
                $snsPyload);
            $this->createXLedgerAccountPushToSNS($snsPyload);
        }

    }

    /***
     * Make a call to ledger to create sub accounts for provided merchant
     * @param Merchant $merchant
     * @param string $mode
     * @param int $balanceAmount
     * @param array $creditBalances
     */
    public function createPGLedgerAccount(Merchant $merchant, string   $mode, int $primaryBalanceAmount, array $creditBalances, int $reserveBalanceAmount, array $amountCreditIdBalances)
    {
        try
        {
            $payload = $this->getPGLedgerAccountCreatePayload($mode, $merchant, self::PG_MERCHANT_ONBOARDING,
                $primaryBalanceAmount, $creditBalances, $reserveBalanceAmount);

            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER    => self::PG,
                LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()->toString()
            ];
            $ledgerService = $this->app['ledger'];
            $ledgerService->createAccountsOnEvent($payload, $requestHeaders, true);

            if (empty($amountCreditIdBalances) === false)
            {
                foreach ($amountCreditIdBalances as $amountCreditId => $value)
                {
                    $amountCreditIdBalance   = $value[0];
                    $amountCreditIdExpiredAt = $value[1];

                    $this->createAmountCreditAccountOnEvent($amountCreditId, $amountCreditIdExpiredAt, $mode, $merchant->getId());

                    $this->loadFundsInAmountCreditAccount($amountCreditId, $amountCreditIdBalance, $merchant->getId());
                }
            }
            return true;
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception and retry in async
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_CREATE_REQUEST_FAILED,
                [
                    self::MERCHANT_ID => $merchant->getMerchantId(),
                    self::TENANT      => self::PG
                ]);
            return false;
        }
    }

    public function createAmountCreditAccountOnEvent($amountCreditId, $amountCreditIdExpiredAt, $mode, $merchantId)
    {
        $eventObj = [
            self::EVENT_NAME            => self::PG_MERCHANT_CREDIT_ONBOARDING,
            self::ENTITIES => [
                self::CREDIT_ID =>  [$amountCreditId],
                self::EXPIRED_AT => [$amountCreditIdExpiredAt]
            ]
        ];

        $payload = [
            self::TENANT            => self::PG,
            self::MODE              => $mode,
            self::MERCHANT_ID       => $merchantId,
            self::EVENTS      => [
                $eventObj
            ],
        ];

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => self::PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];
        $this->app['ledger']->createAccountsOnEvent($payload, $requestHeaders, true);
    }

    public function loadFundsInAmountCreditAccount($amountCreditId, $amountCreditIdBalance, $merchantId)
    {
        $transactionMessage = [];
        $transactionMessage = $this->createTransactionMessageForMerchantAmountCreditLoading($amountCreditIdBalance, $amountCreditId, $merchantId);

        $transactorId = $transactionMessage[Constants::TRANSACTOR_ID];
        $transactorEvent = $transactionMessage[Constants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    public function createTransactionMessageForMerchantAmountCreditLoading($amountCreditIdBalance, $amountCreditId, $merchantId): array
    {
        $transactorEvent = Constants::MERCHANT_AMOUNT_CREDIT_LOADING_V2;
        $amount =  abs($amountCreditIdBalance);

        $msg = array(
            Constants::TRANSACTOR_ID             => 'credits_'.$amountCreditId,
            Constants::TRANSACTOR_EVENT          => $transactorEvent,
            Constants::MONEY_PARAMS              => [
                Constants::AMOUNT_CREDITS   => strval($amount),
                Constants::RAZORPAY_REWARD  => strval($amount)
            ],
            Constants::ADDITIONAL_PARAMS         => null,
            Constants::MERCHANT_ID               => $merchantId,
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          =>  time(),
            Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
            Constants::TENANT                       => Constants::TENANT_PG,
            Constants::IDENTIFIERS => [
                Constants::CREDIT_ID => $amountCreditId,
            ]
        );

        return $msg;
    }

    /**
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function updatePGMerchantBalance(Merchant $merchant, int $balanceAmount)
    {
        try
        {
            $payload = [
                self::MERCHANT_ID => $merchant->getId(),
                self::ENTITIES => [
                    self::ACCOUNT_TYPE => [self::PAYABLE],
                    self::FUND_ACCOUNT_TYPE => [self::MERCHANT_BALANCE]
                ],
                self::BALANCE => strval($balanceAmount),
            ];

            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER => self::PG,
                LedgerService::IDEMPOTENCY_KEY_HEADER => Uuid::uuid1()->toString()
            ];

            $ledgerService = $this->app['ledger'];
            $ledgerResponse = $ledgerService->updateAccountByEntitiesAndMerchantID($payload, $requestHeaders, true);

            if(isset($ledgerResponse["code"]) and $ledgerResponse["code"] == 200 and isset($ledgerResponse["body"]["balance"]))
            {
                $response[self::MERCHANT_BALANCE] = $ledgerResponse["body"]["balance"];
            }
            else
            {
                $response[self::MERCHANT_BALANCE] = self::NOT_UPDATED;

                $this->trace->debug(TraceCode::MERCHANT_BALANCE_SYNC_FAILED, [
                    self::MERCHANT_ID       => $merchant->getId(),
                    self::PAYLOAD           => $payload,
                    self::LEDGER_RESPONSE   => $ledgerResponse
                ]);
            }
            return $response;
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::MERCHANT_BALANCE_SYNC_FAILED,
                [
                    "exception_message"     => $ex->getMessage(),
                    "exception"             => $ex,
                    "merchant_id"           => $merchant->getId()
                ]);
            throw $ex;
        }
    }

    /**
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function updatePGMerchantReserveBalance(Merchant $merchant, int $reserveBalanceAmount)
    {
        try
        {
            $payload = [
                self::MERCHANT_ID => $merchant->getId(),
                self::ENTITIES => [
                    self::ACCOUNT_TYPE => [self::PAYABLE],
                    self::FUND_ACCOUNT_TYPE => [self::MERCHANT_RESERVE_BALANCE]
                ],
                self::BALANCE => strval($reserveBalanceAmount)
            ];

            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER => self::PG,
                LedgerService::IDEMPOTENCY_KEY_HEADER => Uuid::uuid1()->toString()
            ];

            $ledgerService = $this->app['ledger'];
            $ledgerResponse = $ledgerService->updateAccountByEntitiesAndMerchantID($payload, $requestHeaders, true);

            if(isset($ledgerResponse["code"]) and $ledgerResponse["code"] == 200 and isset($ledgerResponse["body"]["balance"]))
            {
                $response[self::MERCHANT_RESERVE_BALANCE] = $ledgerResponse["body"]["balance"];
            }
            else
            {
                $response[self::MERCHANT_RESERVE_BALANCE] = self::NOT_UPDATED;

                $this->trace->debug(TraceCode::MERCHANT_RESERVE_BALANCE_SYNC_FAILED, [
                    self::MERCHANT_ID       => $merchant->getId(),
                    self::PAYLOAD           => $payload,
                    self::LEDGER_RESPONSE   => $ledgerResponse
                ]);
            }
            return $response;
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::MERCHANT_RESERVE_BALANCE_SYNC_FAILED,
                [
                    "exception_message"     => $ex->getMessage(),
                    "exception"             => $ex,
                    "merchant_id"           => $merchant->getId()
                ]);
            throw $ex;
        }
    }

    /**
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function updatePGLedgerMerchantCreditBalances(Merchant $merchant, array $creditBalances): array
    {
        try {
            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER => self::PG,
                LedgerService::IDEMPOTENCY_KEY_HEADER => Uuid::uuid1()->toString()
            ];

            $response = [];

            $ledgerService = $this->app['ledger'];
            if (isset($creditBalances[self::FEE]) === true)
            {
                $feeCredits = (string)$creditBalances[self::FEE];

                $payload = [
                    self::MERCHANT_ID => $merchant->getId(),
                    self::ENTITIES => [
                        self::ACCOUNT_TYPE => [self::PAYABLE],
                        self::FUND_ACCOUNT_TYPE => [self::MERCHANT_FEE_CREDITS]
                    ],
                    self::BALANCE => $feeCredits
                ];
                $feeCreditsResponse = $ledgerService->updateAccountByEntitiesAndMerchantID($payload, $requestHeaders, true);

                if(isset($feeCreditsResponse["code"]) and $feeCreditsResponse["code"] == 200 and isset($feeCreditsResponse["body"]["balance"]))
                {
                    $response[self::MERCHANT_FEE_CREDITS] = $feeCreditsResponse["body"]["balance"];
                }
                else
                {
                    $response[self::MERCHANT_FEE_CREDITS] = self::NOT_UPDATED;

                    $this->trace->debug(TraceCode::MERCHANT_BALANCE_SYNC_FAILED, [
                        self::MERCHANT_ID => $merchant->getId(),
                        self::PAYLOAD => $payload,
                        self::LEDGER_RESPONSE => $feeCreditsResponse
                    ]);
                }
            }

            if (isset($creditBalances[self::AMOUNT]) === true)
            {
                $amountCredits = (string)$creditBalances[self::AMOUNT];
                $payload = [
                    self::MERCHANT_ID => $merchant->getId(),
                    self::ENTITIES => [
                        self::ACCOUNT_TYPE => [self::PAYABLE],
                        self::FUND_ACCOUNT_TYPE => [self::REWARD]
                    ],
                    self::BALANCE => $amountCredits
                ];
                $amountCreditsResponse = $ledgerService->updateAccountByEntitiesAndMerchantID($payload, $requestHeaders);

                if(isset($amountCreditsResponse["code"]) and $amountCreditsResponse["code"] == 200 and isset($amountCreditsResponse["body"]["balance"]))
                {
                    $response[self::MERCHANT_AMOUNT_CREDITS] = $amountCreditsResponse["body"]["balance"];
                }
                else
                {
                    $response[self::MERCHANT_AMOUNT_CREDITS] = self::NOT_UPDATED;

                    $this->trace->debug(TraceCode::MERCHANT_BALANCE_SYNC_FAILED, [
                        self::MERCHANT_ID => $merchant->getId(),
                        self::PAYLOAD => $payload,
                        self::LEDGER_RESPONSE => $amountCreditsResponse
                    ]);
                }
            }

            if (isset($creditBalances[self::REFUND]) === true) {
                $refundCredits = (string)$creditBalances[self::REFUND];
                $payload = [
                    self::MERCHANT_ID => $merchant->getId(),
                    self::ENTITIES => [
                        self::ACCOUNT_TYPE => [self::PAYABLE],
                        self::FUND_ACCOUNT_TYPE => [self::MERCHANT_REFUND_CREDITS]
                    ],
                    self::BALANCE => $refundCredits
                ];
                $refundCreditsResponse = $ledgerService->updateAccountByEntitiesAndMerchantID($payload, $requestHeaders);

                if(isset($refundCreditsResponse["code"]) and $refundCreditsResponse["code"] == 200 and isset($refundCreditsResponse["body"]["balance"]))
                {
                    $response[self::MERCHANT_REFUND_CREDITS] = $refundCreditsResponse["body"]["balance"];
                }
                else
                {
                    $response[self::MERCHANT_REFUND_CREDITS] = self::NOT_UPDATED;

                    $this->trace->debug(TraceCode::MERCHANT_BALANCE_SYNC_FAILED, [
                        self::MERCHANT_ID => $merchant->getId(),
                        self::PAYLOAD => $payload,
                        self::LEDGER_RESPONSE => $refundCreditsResponse
                    ]);
                }
            }

            return $response;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::MERCHANT_BALANCE_SYNC_FAILED,
                [
                    "exception_message"     => $ex->getMessage(),
                    "exception"             => $ex,
                    "merchant_id"           => $merchant->getId()
                ]);
            throw $ex;
        }
    }

    public function createPGLedgerGatewayAccount(string $merchantId, string $mode, string $gateway)
    {
        try
        {
            $payload = $this->getPGLedgerGatewayAccountCreatePayload($mode, $merchantId, self::PG_GATEWAY_ONBOARDING, $gateway);
            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER    => self::PG,
                LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()->toString()
            ];
            $ledgerService = $this->app['ledger'];
            $ledgerService->createAccountsOnEvent($payload, $requestHeaders, true);
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception and retry in async
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_CREATE_REQUEST_FAILED,
                [
                    self::MERCHANT_ID => $merchantId,
                    self::GATEWAY     => $gateway
                ]);
        }
    }

    public function createXLedgerAccountPushToSNS($payload)
    {
        $this->trace->info(TraceCode::LEDGER_ACCOUNT_STREAMING_STARTED, $payload);
        try
        {
            $sns = $this->app['sns'];
            $target = self::LEDGER_ACCOUNT_ONBOARDING;
            $sns->publish(json_encode($payload), $target);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_STREAMING_FAILED,
                $payload);
        }
    }

    public function getLedgerAccountCreateSNSPayload($mode, $merchant, $event, $uniqueIdKey, $uniqueId, $balanceAmount, $creditBalance, $ftsId = null)
    {
        $payload = [
            self::TENANT            => self::X,
            self::MODE              => $mode,
            self::IDEMPOTENCY_KEY   => gen_uuid(self::UUID_FORMAT),
            self::MERCHANT_ID       => $merchant->getId(),
            self::EVENT             => [
                self::EVENT_NAME            => $event,
                self::EVENT_DESCRIPTION     => $this->eventDescription[$event],
                self::ENTITIES              => [
                    $uniqueIdKey => [$uniqueId],
                ],
            ],
        ];

        if ($ftsId !== null)
        {
            $payload[self::EVENT][self::ENTITIES][self::FTS_FUND_ACCOUNT_ID] = [$ftsId];
        }

        if ($balanceAmount !== 0)
        {
            $payload[self::MERCHANT_BALANCE_OPENING_BALANCE] = (string) $balanceAmount;
        }

        if ($creditBalance !== 0)
        {
            $payload[self::MERCHANT_REWARD_OPENING_BALANCE] = (string) $creditBalance;
        }

        return $payload;
    }

    public function getLedgerAccountCreatePayload($mode, $merchant, $event, $uniqueIdKey, $uniqueId, $balanceAmount, $creditBalance, $ftsId = null)
    {
        $eventObj = [
            self::EVENT_NAME            => $event,
            self::EVENT_DESCRIPTION     => $this->eventDescription[$event],
            self::ENTITIES              => [
                $uniqueIdKey => [$uniqueId],
            ],
        ];

        if ($ftsId !== null)
        {
            $eventObj[self::ENTITIES][self::FTS_FUND_ACCOUNT_ID] = [$ftsId];
        }

        $payload = [
            self::TENANT            => self::X,
            self::MODE              => $mode,
            self::IDEMPOTENCY_KEY   => gen_uuid(self::UUID_FORMAT),
            self::MERCHANT_ID       => $merchant->getId(),
            self::EVENTS             => [
                $eventObj
            ],
        ];

        if ($balanceAmount !== 0)
        {
            $payload[self::MERCHANT_BALANCE_OPENING_BALANCE] = (string) $balanceAmount;
        }

        if ($creditBalance !== 0)
        {
            $payload[self::MERCHANT_REWARD_OPENING_BALANCE] = (string) $creditBalance;
        }

        $this->trace->info(
            TraceCode::LEDGER_REQUEST_PAYLOAD_CREATED,
            [
                'payload'               => $payload,
            ]
        );

        return $payload;
    }

    public function getPGLedgerAccountCreatePayload($mode, $merchant, $event, $primaryBalanceAmount, $creditBalances, $reserveBalanceAmount): array
    {
        $eventObj = [
            self::EVENT_NAME            => $event,
            self::EVENT_DESCRIPTION     => $this->eventDescription[$event],
            self::ENTITIES              => (object) [],
        ];

        $payload = [
            self::TENANT            => self::PG,
            self::MODE              => $mode,
            self::MERCHANT_ID       => $merchant->getId(),
            self::EVENTS             => [
                $eventObj
            ],
        ];

        $openingBalances = [];
        if ($primaryBalanceAmount !== 0)
        {
            $openingBalances[self::MERCHANT_BALANCE_OPENING_BALANCE] = (string) $primaryBalanceAmount;
        }

        if(isset($creditBalances[self::FEE]) === true)
        {
            $openingBalances[self::MERCHANT_FEE_OPENING_BALANCE] = (string) $creditBalances[self::FEE];
        }

        if(isset($creditBalances[self::REFUND]) === true)
        {
            $openingBalances[self::MERCHANT_REFUND_OPENING_BALANCE] = (string) $creditBalances[self::REFUND];
        }

        if ($reserveBalanceAmount !== 0)
        {
            $openingBalances[self::MERCHANT_RESERVE_OPENING_BALANCE] = (string) $reserveBalanceAmount;
        }

        if (count($openingBalances) > 0){
            $payload[self::MERCHANT_OPENING_BALANCES] = $openingBalances;
        }

        $this->trace->info(
            TraceCode::LEDGER_REQUEST_PAYLOAD_CREATED,
            [
                'payload'               => $payload,
                self::TENANT            => self::PG
            ]
        );

        return $payload;
    }

    public function getPGLedgerGatewayAccountCreatePayload($mode, $merchantId, $event, $gateway): array
    {
        $eventObj = [
            self::EVENT_NAME            => $event,
            self::EVENT_DESCRIPTION     => $this->eventDescription[$event],
            self::ENTITIES              => [
                self::GATEWAY => array($gateway)
            ],
        ];

        $payload = [
            self::TENANT            => self::PG,
            self::MODE              => $mode,
            self::MERCHANT_ID       => $merchantId,
            self::EVENTS            => [
                $eventObj
            ]
        ];

        $this->trace->info(
            TraceCode::LEDGER_REQUEST_PAYLOAD_CREATED,
            [
                'payload'               => $payload,
                self::TENANT            => self::PG
            ]
        );

        return $payload;
    }

    /***
     * Push event to sns topic which will be consumed by ledger SQS to create accounts based on event for direct accounting
     * @param Merchant $merchant
     * @param BankingAccount $bankingAccount
     * @param string $mode
     * @param string $accountType can be direct (for Direct Accounts)
     * @param int $balanceAmount
     * @param int $creditBalance
     * @param bool $isReverseShadow
     */
    public function createXLedgerAccountForDirect(Merchant $merchant, BankingAccountStatementDetails $bankingAccountStmtDetails,
                                                 string $mode, int $balanceAmount = 0, int $creditBalance = 0, bool $isReverseShadow = false)
    {
        $event = self::DIRECT_MERCHANT_ONBOARDING;

        $snsPyload = $this->getLedgerAccountCreateSNSPayload($mode, $merchant, $event, self::BANKING_ACCOUNT_STMT_DETAILS_ID, $bankingAccountStmtDetails->getPublicId(), $balanceAmount, $creditBalance, null);
        if ($isReverseShadow === false)
        {
            // onboard to ledger in async for shadow mode
            $this->createXLedgerAccountPushToSNS($snsPyload);
            return;
        }

        try
        {
            // onboard to ledger in sync for reverse shadow mode
            $payload = $this->getLedgerAccountCreatePayload($mode, $merchant, $event, self::BANKING_ACCOUNT_STMT_DETAILS_ID, $bankingAccountStmtDetails->getPublicId(), $balanceAmount, $creditBalance, null);
            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER    => self::X,
                LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
            ];
            $ledgerService = $this->app['ledger'];
            $ledgerService->createAccountsOnEvent($payload, $requestHeaders, true);
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception and retry in async
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_CREATE_REQUEST_FAILED,
                $snsPyload);
            $this->createXLedgerAccountPushToSNS($snsPyload);
        }

    }

    /**
     * This function is called to fetch merchant balance and credit (reward) balance from ledger service.
     * @param string $merchantId
     * @param string $bankingAccountId
     * @return array
     */
    public function fetchBalanceFromLedger(Merchant $merchant, string $bankingAccountId, int $maxRetryCount = self::DEFAULT_MAX_RETRY_COUNT, int $retryCount = 0) :array {
            $startTime = millitime();
            $ledgerResponse = [];
            $ledgerBalanceFetchTiDBEnabled = false;

            try {

                $request = [
                    self::TENANT             => self::X,
                    self::MERCHANT_ID        => $merchant->getId(),
                    self::BANKING_ACCOUNT_ID => $bankingAccountId,
                ];

                $requestHeaders = [
                    LedgerService::LEDGER_TENANT_HEADER => self::X,
                    Ledger\Base::COUNTRY_CODE           => $merchant->getCountry()
                ];

                $ledgerBalanceFetchTiDBEnabled = $this->isBalanceFetchFromLedgerTiDBEnabled($merchant->getId(), $this->mode);

                if($ledgerBalanceFetchTiDBEnabled)
                {
                    $ledgerResponse = $this->fetchBalanceFromLedgerTiDB($merchant->getId());
                }
                else
                {
                    $response = $this->ledgerService->fetchMerchantAccounts($request, $requestHeaders);
                    $statusCode = $response[LedgerService::RESPONSE_CODE];

                    if ($statusCode !== 200)
                    {
                        if ($retryCount < $maxRetryCount)
                        {
                            $retryCount++;
                            return $this->fetchBalanceFromLedger($merchant, $bankingAccountId, $maxRetryCount, $retryCount);
                        }
                        else
                        {
                            throw new ServerErrorException('Received invalid status code',
                                ErrorCode::SERVER_ERROR_LEDGER_ACCOUNT_FETCH_BALANCES,
                                [
                                    LedgerService::RESPONSE_CODE => $statusCode,
                                    LedgerService::RESPONSE_BODY => $ledgerResponse,
                                ]
                            );
                        }
                    }

                    $ledgerResponse = $response[LedgerService::RESPONSE_BODY];
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_ERROR,
                    [
                        self::MERCHANT_ID        => $merchant->getId(),
                        self::BANKING_ACCOUNT_ID => $bankingAccountId
                    ]);

                if($ledgerBalanceFetchTiDBEnabled === false)
                {
                    // Calling Ledger TIDB here, only as fallback if ledger service returns an error.
                    $this->trace->info(TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_FROM_TIDB,
                        [
                            self::MERCHANT_ID        => $merchant->getId(),
                            self::BANKING_ACCOUNT_ID => $bankingAccountId
                        ]);

                    $ledgerResponse = $this->fetchBalanceFromLedgerTiDB($merchant->getId());
                }

            }
            finally
            {
                $this->trace->info(
                    TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_TIME_TAKEN,
                    [
                        self::TIME_TAKEN => millitime() - $startTime,
                    ]);
            }
            return $ledgerResponse;
    }

    /**
     * This function changes credit balance amount with the credit balance returned by ledger service.
     * @param array $creditBalances
     * @param array $ledgerResponse
     */
    public function constructCreditBalanceFromLedger(array &$creditBalances, array $ledgerResponse){
        foreach ($creditBalances as &$creditBalance)
        {
            if ($creditBalance[\RZP\Models\Merchant\Credits\Entity::TYPE] !== Type::FEE_CREDIT)
            {
                $creditBalance[CreditEntity::BALANCE] = (int) $ledgerResponse[self::REWARD_BALANCE][self::BALANCE];
            }
        }
    }

    /**
     * This function will fetch balance from ledger TiDB when ledger service is down.
     * The array returned by this function is same as the response returned by the ledger service.
     * @param string $merchantId
     * @param string $bankingAccountId
     * @return array
     */
    private function fetchBalanceFromLedgerTiDB(string $merchantId) :array
    {
        $balanceResponse = [];
        $startTime = millitime();

        try
        {

            $merchantAccounts = $this->repo->account_detail->fetchBalanceByFundAccountType($merchantId, Ledger\Base::MERCHANT_VA, ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

            $rewardAccounts = $this->repo->account_detail->fetchBalanceByFundAccountType($merchantId, Ledger\Base::REWARD, ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

            $this->trace->info(TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_FROM_TIDB_RESPONSE,
                [
                    'merchant_accounts' => $merchantAccounts,
                    'reward_accounts'   => $rewardAccounts,
                ]
            );

            $balanceResponse = [
                Ledger\Base::MERCHANT_ID      => $merchantId,
                Ledger\Base::MERCHANT_BALANCE => $this->constructLedgerBalanceResponse(Ledger\Base::MERCHANT_VA, Ledger\Base::PAYABLE, $merchantAccounts),
                Ledger\Base::REWARD_BALANCE   => $this->constructLedgerBalanceResponse(Ledger\Base::REWARD, Ledger\Base::PAYABLE, $rewardAccounts),
            ];

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_FROM_TIDB_ERROR,
                [
                    self::MERCHANT_ID  => $merchantId,
                ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_FROM_TIDB_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
        return $balanceResponse;
    }

    /**
     * This function constructs ledger balance response from the Ledger TiDB query result.
     * @param string $fundAccountType
     * @param string $accountType
     * @param array $ledgerAccounts
     * @return array
     */
    private function constructLedgerBalanceResponse(string $fundAccountType, string $accountType, array $ledgerAccounts) :array {
        $balance = [];

        foreach($ledgerAccounts as $account) {

            $entities = json_decode($account[Ledger\Base::ENTITIES], true);

            if ((empty($entities[Ledger\Base::FUND_ACCOUNT_TYPE]) === true) ||
                (empty($entities[Ledger\Base::ACCOUNT_TYPE]) === true)) {
                continue;
            }

            if (($entities[Ledger\Base::FUND_ACCOUNT_TYPE][0] === $fundAccountType) and
                ($entities[Ledger\Base::ACCOUNT_TYPE][0] === $accountType))
            {
                $balance = [
                    Ledger\Base::BALANCE     => $account[Ledger\Base::BALANCE],
                    Ledger\Base::MIN_BALANCE => $account[Ledger\Base::MIN_BALANCE],
                ];
            }
        }
        return $balance;
    }

    /**
     * This function updates merchnat balnce on ledger by mid and entities for Direct accounts
     * @param string $merchantId
     * @param string $basdId
     */
    public function updateXLedgerMerchantBalanceAccountForDirect(string $merchantId, string $basdId, $balance = null, $minBalance = null)
    {

        try
        {
            if (($balance === null) && ($minBalance == null))
            {
                // nothing to update
                return;
            }

            // payload to update merchant balance account of DA
            $payload = [
                self::MERCHANT_ID => $merchantId,
                self::ENTITIES => [
                    self::BANKING_ACCOUNT_STMT_DETAILS_ID   => [$basdId],
                    self::ACCOUNT_TYPE                      => [self::PAYABLE],
                    self::FUND_ACCOUNT_TYPE                 => [self::MERCHANT_DA]
                ]
            ];

            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER => self::X,
            ];

            if ($balance != null)
            {
                $payload[self::BALANCE] = (string) $balance;
            }

            if ($minBalance != null)
            {
                $payload[self::MIN_BALANCE] = (string) $minBalance;
            }

            $this->trace->info(
                TraceCode::LEDGER_ACCOUNT_UPDATE_REQUEST,
                [
                    'request' => $payload,
                ]);

            $ledgerService = $this->app['ledger'];
            $ledgerService->updateAccountByEntitiesAndMerchantID($payload, $requestHeaders, true);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_UPDATE_REQUEST_FAILED,
                $payload);
        }

    }

    // Returns true if experiment and env variable to fetch balance from ledger TiDB is running.
    protected function isBalanceFetchFromLedgerTiDBEnabled(string $merchantId, string $mode): bool
    {
        $variant = $this->app->razorx->getTreatment($merchantId,
            RazorxTreatment::LEDGER_BALANCE_FETCH_FROM_TIDB,
            $mode
        );

        return (strtolower($variant) === 'on');
    }

    public function getLedgerOnDemandSettlementAccountCreatePayload(Merchant $merchant, string $mode, string $event): array
    {
        $eventObj = [
            self::EVENT_NAME            => $event,
        ];

        $payload = [
            self::TENANT            => self::PG, // do we need tenant? not sending via collections
            self::MODE              => $mode,
            self::MERCHANT_ID       => $merchant->getId(),
            self::EVENTS            => [
                $eventObj
            ],
        ];

        $this->trace->info(
            TraceCode::LEDGER_REQUEST_PAYLOAD_ES_ONDEMAND_CREATED,
            [
                'payload'               => $payload,
                self::TENANT            => self::PG
            ]
        );
        return $payload;
    }

    public function createLedgerOndemandSettlementAccount(Merchant $merchant, string $mode)
    {
        try
        {
            $payload = $this->getLedgerOnDemandSettlementAccountCreatePayload($merchant, $mode, self::LEDGER_ONDEMAND_SETTLEMENT_ONBOARDING);

            $requestHeaders = [
                LedgerService::LEDGER_TENANT_HEADER    => self::PG,
                LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()->toString()
            ];
            $ledgerService = $this->app['ledger'];
            $ledgerService->createAccountsOnEvent($payload, $requestHeaders, true);
            // TODO add logs
            return true;
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception and retry in async
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_ACCOUNT_ES_ONDEMAND_CREATE_REQUEST_FAILED,
                [
                    self::MERCHANT_ID => $merchant->getMerchantId(),
                    self::TENANT      => self::PG,
                    self::EVENT_NAME  => self::LEDGER_ONDEMAND_SETTLEMENT_ONBOARDING,
                ]);

            $this->trace->count(Metric::LEDGER_ACCOUNT_CREATION_FAILURE, [
                self::EVENT_NAME => self::LEDGER_ONDEMAND_SETTLEMENT_ONBOARDING,
            ]);
            return false;
        }
    }
}
