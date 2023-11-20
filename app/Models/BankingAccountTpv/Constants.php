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

}
