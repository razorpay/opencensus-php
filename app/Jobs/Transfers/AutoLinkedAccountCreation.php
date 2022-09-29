<?php
namespace RZP\Jobs\Transfers;

use App;
use RZP\Base\RuntimeManager;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account\Core;

class AutoLinkedAccountCreation extends Job
{
    protected $merchantId;

    protected $queueConfigKey = 'linked_account_batch';

    public $timeout = 1200;

    public function __construct(string $mode, string $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->count('auto_linked_account_new_merchants');

        try
        {
            $this->trace->info(TraceCode::AUTO_AMC_LINKED_ACCOUNT_CREATION_INITIATED, [
                "merchant_id" => $this->merchantId,
                "mode"        => $this->mode
            ]);

            $startTime = microtime(true);

            $response = (new Core())->createAutoAMCAccountsForMFDMerchants($this->merchantId);

            $endTime = microtime(true);

            $this->trace->info(TraceCode::AUTO_AMC_LINKED_ACCOUNT_CREATION_COMPLETED,
                [
                    'time_taken'                   => $endTime - $startTime,
                    'merchant_id'                  => $this->merchantId,
                    'mode'                         => $this->mode,
                    'response'                     => $response
                ]);
        }
        catch( \Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::AUTO_AMC_LINKED_ACCOUNT_CREATION_FAILED, [
                "merchant_id" => $this->merchantId,
                "mode"        => $this->mode
             ]);
        }
        finally
        {
            $this->delete();
        }
    }
}
