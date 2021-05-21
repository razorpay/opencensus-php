<?php

namespace RZP\Models\Merchant\Balance\Ledger;

use App;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\BankingAccount\Entity as BankingAccount;

class Core extends Base\Core
{
    const LEDGER_ACCOUNT_ONBOARDING = 'ledger_account_onboarding';
    const DIRECT_ACCOUNT_ONBOARDING = 'direct_account_onboarding';
    const SHARED_ACCOUNT_ONBOARDING = 'shared_account_onboarding';

    const MODE                  = 'mode';
    const TRANSACTOR            = 'transactor';
    const MERCHANT_ID           = 'merchant_id';
    const EVENT                 = 'event';
    const EVENT_NAME            = 'name';
    const EVENT_DESCRIPTION     = 'description';
    const ENTITIES              = 'entities';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';

    const X = 'X';

    const SHARED = 'shared';
    const DIRECT = 'direct';

    protected $eventDescription = [
        self::DIRECT_ACCOUNT_ONBOARDING  => 'Event for onboarding of merchant on direct account',
        self::SHARED_ACCOUNT_ONBOARDING  => 'Event for onboarding of merchant on shared account',
    ];

    /***
     * Push event to sns topic which will be consumed by ledger SQS to create accounts based on event
     * @param Merchant $merchant
     * @param BankingAccount $bankingAccount
     * @param string $mode
     * @param string $accountType can be shared (for Virtual Accounts) or direct (for Current Accounts)
     */
    public function createXLedgerAccount(Merchant $merchant, BankingAccount $bankingAccount,
                                         string $mode, $accountType = self::SHARED)
    {
        $event = $accountType == self::SHARED ? self::SHARED_ACCOUNT_ONBOARDING : self::DIRECT_ACCOUNT_ONBOARDING;

        $payload = [
            self::TRANSACTOR        => self::X,
            self::MODE              => $mode,
            self::MERCHANT_ID       => $merchant->getId(),
            self::EVENT             => [
                self::EVENT_NAME            => $event,
                self::EVENT_DESCRIPTION     => $this->eventDescription[$event]
            ],
        ];

        if ($bankingAccount->getFtsFundAccountId() !== null)
        {
            $payload[self::EVENT][self::ENTITIES] = [
                self::FTS_FUND_ACCOUNT_ID => [$bankingAccount->getFtsFundAccountId()],
            ];
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
}
