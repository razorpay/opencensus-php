<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;

class CreateAccount extends Job
{
    const MAX_RETRY_ATTEMPTS   = 0;

    const MAX_ALLOWED_ATTEMPTS = 10;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_create_account';

    protected $id;

    protected $type;

    public function __construct(string $id, string $mode, string $type)
    {
        parent::__construct($mode);

        $this->id  = $id;

        $this->type = $type;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                return;
            }

            parent::handle();

            $this->trace->info(TraceCode::FTS_CREATE_ACCOUNT,
                [
                    'id'    => $this->id ,
                    "type"  => $this->type,
                ]);

            $ftsResponse = App::getFacadeRoot()['fts_create_account']->createFundAccount($this->id, $this->type);

            $this->trace->info(
                TraceCode::FTS_ACCOUNT_CREATED_FOR_MERCHANT,
                $ftsResponse);
        }
        catch (\Throwable $e)
        {

            $data = [
                'id'                => $this->id ,
                'type'              => $this->type,
                'mode'              => $this->mode,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTS_ACCOUNT_CREATION_FAILED,
                $data);
        }
    }
}
