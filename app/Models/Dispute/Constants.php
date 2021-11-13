<?php


namespace RZP\Models\Dispute;


class Constants
{
    const CUSTOMER_DISPUTE_GATEWAY_DISPUTE_ID_PREFIX = 'DISPUTE';

    const FD_IND_INSTANCE_ROLLOUT_TS = 1626028200; // 12th July, 2021

    const DEFAULT_INTERNAL_RESPOND_BY_IN_SECONDS     = (24 * 60 * 60) * 10;

    const GATEWAY_DISPUTE_SOURCE_CUSTOMER = 'customer';

    const GATEWAY_DISPUTE_SOURCE_NETWORK = 'network';

    const DEFAULT_DEDUCTION_REVERSAL_AT_IN_SECONDS = (24 * 60 * 60) * 45;
}
