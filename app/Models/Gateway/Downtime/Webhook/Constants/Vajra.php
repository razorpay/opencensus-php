<?php

namespace RZP\Models\Gateway\Downtime\Webhook\Constants;

class Vajra
{
    const STATUS_KEY      = 'state';

    const STATUS_ALERTING = 'alerting';

    const STATUS_OK       = 'ok';

    const DETAILS_DATA_KEY           = 'message';

    const DETAILS_DATA_MERCHANTS_KEY = 'merchant_ids';

    const DETAILS_DATA_MERCHANT_KEY  = 'merchant_id';

    const DETAILS_DATA_TERMINALS_KEY = 'terminal_ids';

    const DETAILS_DATA_TERMINAL_KEY  = 'terminal_id';

    const DETAILS_DATA_GATEWAY_KEY   = 'gateway';

    const DETAILS_DATA_METHOD_KEY    = 'method';

    const EVAL_MATCHES_KEY = 'evalMatches';

    const RULE_ID_KEY      = 'ruleId';

    const RULE_NAME_KEY    = 'ruleName';

    const RULE_URL_KEY     = 'ruleUrl';

    const TITLE_KEY        = 'title';

	private function __construct()
	{

	}
}
