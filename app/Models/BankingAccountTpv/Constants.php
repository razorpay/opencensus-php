<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Models\Settlement\Channel;

class Constants
{
    const SOURCE_ACCOUNT_DETAILS = 'source_account_details';

    const SOURCE_ACCOUNT_ADDITION_MOZART_ACTION = 'source_account_addition';

    const SOURCE_ACCOUNT_ADDITION_MOZART_NAMESPACE = 'fts';

    // Mozart request/ response fields

    const DATA = 'data';

    const STATUS = 'status';

    const SUCCESS = 'SUCCESS';

    const REQUEST_ID = 'request_id';

    const CLIENT_IDENTIFIER = 'client_identifier';

    const SOURCE_ACCOUNT_NUMBER = 'source_account_number';

    const SOURCE_ACCOUNT_IFSC = 'source_account_ifsc';

    // Yesbank Source Account Addition Error Codes
    const DCA018 = "DCA018";

    const NON_ACTIONABLE_ERROR_CODES = [
        Channel::YESBANK => [
            self::DCA018
        ],
    ];

    // Remarks Constants for tpv migration for POBO
    const RX_WALLET_TPV_MIGRATION_SUCCESSFUL = 'RX_WALLET_TPV_MIGRATION_SUCCESSFUL';
    const RX_WALLET_TPV_MIGRATION_FAILURE    = 'RX_WALLET_TPV_MIGRATED_FAILURE';

    // Banking Account TPV Migration Constants
    const TPV_MIGRATION_MAP           = 'tpv_migration_map';
    const TPV_MIGRATION_INVALID_INPUT = 'Invalid input provided for TPV Migration.';
    const BANKING_ACCOUNT_TPV_ID      = 'banking_account_tpv_id';
    const MIGRATION_BALANCE_ID        = 'migration_balance_id';
}
