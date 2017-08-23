<?php

namespace RZP\Models\Payment\Verify;

class Action
{
    /**
     * Disbale gateway for verify for given period
     */
    const BLOCK = 'block';

    /**
     * Don't run verify for this payment again
     */
    const FINISH  = 'finish';

    /**
     * Ignore the current run, and get picked in immediate next run
     */
    const RETRY = 'retry';
}
