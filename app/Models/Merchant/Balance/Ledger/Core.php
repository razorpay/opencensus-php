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

    const IDEMPOTENCY_KEY = 'idempotency_key';
    const UUID_FORMAT     = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';

    const X = 'X';

    const SHARED = 'shared';
    const DIRECT = 'direct';

    protected $eventDescription = [
        self::DIRECT_MERCHANT_ONBOARDING  => 'Event for onboarding of merchant on direct account',
        self::SHARED_MERCHANT_ONBOARDING  => 'Event for onboarding of merchant on shared account',
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
