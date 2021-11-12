<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;

class Base extends Core
{
    const TENANT                = 'tenant';
    const MODE                  = 'mode';
    const MERCHANT_ID           = 'merchant_id';
    const BALANCE_ID            = 'balance_id';
    const CURRENCY              = 'currency';
    const AMOUNT                = 'amount';
    const BASE_AMOUNT           = 'base_amount';
    const COMMISSION            = 'commission';
    const TAX                   = 'tax';
    const NOTES                 = 'notes';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';
    const FTS_ACCOUNT_TYPE      = 'fts_account_type';
    const TERMINAL_ID           = 'terminal_id';
    const TERMINAL_ACCOUNT_TYPE = 'terminal_account_type';
    const TRANSACTION_ID        = 'transaction_id';
    const TRANSACTION_DATE      = 'transaction_date';
    const TRANSACTOR_ID         = 'transactor_id';
    const TRANSACTOR_EVENT      = 'transactor_event';
    const TRANSACTION_CONFIG_ID = 'transaction_config_id';
    const ENTITY                = 'entity';
    const IDEMPOTENCY_KEY       = 'idempotency_key';
    const BANKING_ACCOUNT_ID    = 'banking_account_id';
    const API_TRANSACTION_ID    = 'api_transaction_id';
    const IDENTIFIERS           = 'identifiers';
    const ADDITIONAL_PARAMS     = 'additional_params';

    // For Fee Credit accounting
    const FEE_ACCOUNTING        = 'fee_accounting';
    const REWARD                = 'reward';

    const UUID_FORMAT = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';

    const TIME_TAKEN = 'time_taken';

    const DEFAULT_EVENT                            = "default_event";
    const DEFAULT_FTS_FUND_ACCOUNT_ID              = '100000000';
    const DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_ID   = '100000001';
    const DEFAULT_M2P_FTS_FUND_ACCOUNT_ID          = '100000002';
    const DEFAULT_FTS_FUND_ACCOUNT_TYPE            = 'nodal';
    const DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_TYPE = 'amazonpay';
    const DEFAULT_M2P_FTS_FUND_ACCOUNT_TYPE        = 'm2p';
    const DEFAULT_TERMINAL_ACCOUNT_TYPE            = 'nodal';

    const X = 'X';

    const LEDGER_TRANSACTION_CREATE = 'ledger_transaction_create';

    /**
     * @param array $payload
     * This function pushes the payload to sns which will be used by ledger. This is
     * being done only for shadow mode and will not depend on ledger's response.
     * Later on, API will directly interact with ledger to create transactions instead of
     * this sns flow, and would use the ledger's response.
     */
    protected function pushToLedgerSns(array $payload)
    {
        $this->trace->info(TraceCode::LEDGER_JOURNAL_STREAMING_STARTED, $payload);

        try
        {
            $sns = $this->app['sns'];

            $target = self::LEDGER_TRANSACTION_CREATE;

            $sns->publish(json_encode($payload), $target);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_STREAMING_FAILED,
                $payload);
        }
    }

    /***
     * @param string $event
     *
     * @return bool
     */
    protected function isDefaultEvent(string $event)
    {
        return ($event === self::DEFAULT_EVENT);
    }

}
