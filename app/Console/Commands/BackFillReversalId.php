<?php

namespace RZP\Console\Commands;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Refund;
use Illuminate\Console\Command;

/**
 * ReversalId column was added in Linked Account dashboard release and
 * we added reversal_id column in refunds table and need to backfill the old reversals data.
 *
 * Class BackFillReversalId
 * @package RZP\Console\Commands
 */
class BackFillReversalId extends Command
{
    protected $signature = 'rzp:backfill:reversal_id
                            {mode           : Database & application mode the command will run in (test|live)}
                            {--skip=0       : Skip offset (eg. skip first 100 rows) }
                            {--take=5000    : Take count (eg. 1000 at a time) }
                            {--db_save      : Dry run the command}';

    protected $description = 'Back fills reversal_id in to refunds table for further consumption';

    protected $mode;
    protected $skip;
    protected $take;

    protected $app;
    protected $trace;
    protected $repo;

    protected $dbSave;

    public function handle()
    {
        $this->setOptions();

        $this->init();

        $this->process();
    }

    protected function setOptions()
    {
        $this->mode        = $this->argument('mode');
        $this->skip        = (int) $this->option('skip');
        $this->take        = (int) $this->option('take');
        $this->dbSave      = (bool) $this->option('db_save');
    }

    protected function init()
    {
        \Database\DefaultConnection::set($this->mode);

        $this->app = App::getFacadeRoot();

        $this->app['rzp.mode'] = $this->mode;

        $this->trace = $this->app['trace'];
        $this->repo = $this->app['repo'];
    }

    protected function process()
    {
        $reversals = $this->repo->reversal->fetchReversalsList($this->skip, $this->take);

        foreach ($reversals as $reversal)
        {
            $transfer = $reversal->entity;

            $merchantId = $transfer->to->id;

            $payment = $this->repo->payment->findByTransferIdAndMerchant($transfer->getId(), $merchantId);

            $refunds = $payment->refunds()
                               ->where(Refund\Entity::AMOUNT, $reversal->getAmount())
                               ->whereNull(Refund\Entity::REVERSAL_ID)
                               ->get();

            if (count($refunds) === 0)
            {
                $this->trace->info(
                    TraceCode::REVERSAL_REFUND_NOT_AVAILABLE,
                    [
                        'reversal_id' => $reversal->getId(),
                    ]);
                continue;
            }

            $refund = $refunds->first();

            $this->trace->info(
                TraceCode::REVERSAL_REFUND_AVAILABLE,
                [
                    'reversal_id' => $reversal->getId(),
                    'refund_id'   => $refund->getId(),
                ]
            );

            if ($this->dbSave === true)
            {
                $refund->reversal()->associate($reversal);
                $this->repo->saveOrFail($refund);
            }
        }
    }
}

