<?php


namespace RZP\Jobs;


class TransferLedgerOutboxPush extends TransferProcess
{
    protected $queueConfigKey = 'transfer_ledger_outbox_push';
}
