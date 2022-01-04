<?php

namespace RZP\Models\Merchant\Balance\Ledger;

use App;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\BankingAccount\Entity as BankingAccount;
use RZP\Models\Merchant\Credits\Balance\Entity as CreditEntity;

class Core extends Base\Core
{
    const LEDGER_ACCOUNT_ONBOARDING = 'ledger_account_onboarding';
    const DIRECT_MERCHANT_ONBOARDING = 'direct_merchant_onboarding';
    const SHARED_MERCHANT_ONBOARDING = 'shared_merchant_onboarding';

    const MODE                  = 'mode';
    const TENANT                = 'tenant';
    const MERCHANT_ID           = 'merchant_id';
    const EVENT                 = 'event';
    const EVENT_NAME            = 'name';
    const EVENT_DESCRIPTION     = 'description';
    const ENTITIES              = 'entities';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';
    const BANKING_ACCOUNT_ID    = 'banking_account_id';

    const MERCHANT_BALANCE_OPENING_BALANCE = 'merchant_balance_opening_balance';
    const MERCHANT_REWARD_OPENING_BALANCE = 'merchant_reward_opening_balance';

    const IDEMPOTENCY_KEY = 'idempotency_key';
    const UUID_FORMAT     = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';

    const X = 'X';

    const SHARED = 'shared';
    const DIRECT = 'direct';

    protected $eventDescription = [
        self::DIRECT_MERCHANT_ONBOARDING  => 'Event for onboarding of merchant on direct account',
        self::SHARED_MERCHANT_ONBOARDING  => 'Event for onboarding of merchant on shared account',
    ];

    const TIME_TAKEN       = 'time_taken';
    const BALANCE          = 'balance';
    const REWARD_BALANCE   = 'reward_balance';
    const MERCHANT_BALANCE = 'merchant_balance';

    /** @var LedgerService $ledgerService */
    protected $ledgerService;

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
     */
    public function createXLedgerAccount(Merchant $merchant, BankingAccount $bankingAccount,
                                         string   $mode, string $accountType = self::SHARED,
                                         int $balanceAmount = 0, int $creditBalance = 0)
    {
        $event = $accountType == self::SHARED ? self::SHARED_MERCHANT_ONBOARDING : self::DIRECT_MERCHANT_ONBOARDING;

        $payload = [
            self::TENANT            => self::X,
            self::MODE              => $mode,
            self::IDEMPOTENCY_KEY   => gen_uuid(self::UUID_FORMAT),
            self::MERCHANT_ID       => $merchant->getId(),
            self::EVENT             => [
                self::EVENT_NAME            => $event,
                self::EVENT_DESCRIPTION     => $this->eventDescription[$event],
                self::ENTITIES              => [
                    self::BANKING_ACCOUNT_ID => [$bankingAccount->getPublicId()],
                ],
            ],
        ];

        if ($bankingAccount->getFtsFundAccountId() !== null)
        {
            $payload[self::EVENT][self::ENTITIES][self::FTS_FUND_ACCOUNT_ID] = [$bankingAccount->getFtsFundAccountId()];
        }

        if ($balanceAmount !== 0)
        {
            $payload[self::MERCHANT_BALANCE_OPENING_BALANCE] = (string) $balanceAmount;
        }

        if ($creditBalance !== 0)
        {
            $payload[self::MERCHANT_REWARD_OPENING_BALANCE] = (string) $creditBalance;
        }

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

    /**
     * This function is called to fetch merchant balance and credit (reward) balance from ledger service.
     * @param string $merchantId
     * @param string $bankingAccountId
     * @return array
     */
    public function fetchBalanceFromLedger(string $merchantId, string $bankingAccountId) :array {
            $startTime = millitime();
            $ledgerResponse = [];

            try {

                $request = [
                    self::TENANT             => self::X,
                    self::MERCHANT_ID        => $merchantId,
                    self::BANKING_ACCOUNT_ID => $bankingAccountId,
                ];

                $response = $this->ledgerService->fetchMerchantAccounts($request);
                $statusCode = $response[LedgerService::RESPONSE_CODE];

                if ($statusCode !== 200)
                {
                    throw new ServerErrorException('Received invalid status code',
                        ErrorCode::SERVER_ERROR_LEDGER_ACCOUNT_FETCH_BALANCES,
                        [
                            LedgerService::RESPONSE_CODE => $statusCode,
                            LedgerService::RESPONSE_BODY => $ledgerResponse,
                        ]
                    );
                }

                $ledgerResponse = $response[LedgerService::RESPONSE_BODY];

            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_ACCOUNT_FETCH_BALANCE_ERROR,
                    [
                        self::MERCHANT_ID        => $merchantId,
                        self::BANKING_ACCOUNT_ID => $bankingAccountId
                    ]);
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
            $creditBalance[CreditEntity::BALANCE] = (int) $ledgerResponse[self::REWARD_BALANCE][self::BALANCE];
        }
    }
}
