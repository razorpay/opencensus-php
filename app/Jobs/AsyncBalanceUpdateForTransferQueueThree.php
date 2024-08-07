<?php

namespace RZP\Jobs;

use App;

/**
 * Job class to update the balance for the transfer transaction and transfer payment transactions
 * using prod-api-transfer-async-balance-update-one-live
 *
 */
class AsyncBalanceUpdateForTransferQueueThree extends AsyncBalanceUpdateForTransfer
{
    protected $queueConfigKey = 'transfer_async_balance_update_queue_three';
}
