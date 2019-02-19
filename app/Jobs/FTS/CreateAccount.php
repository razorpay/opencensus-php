<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;

class CreateAccount extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 10;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $product;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_create_account';

    public function __construct(string $mode, string $id, string $type, string $product)
    {
        parent::__construct($mode);

        $this->id   = $id;

        $this->type = $type;

        $this->product = $product;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::FTS_CREATE_ACCOUNT_INIT,
                [
                    'id'      => $this->id,
                    'type'    => $this->type,
                    'product' => $this->product,
                ]);

            $ftsResponse = App::getFacadeRoot()['fts_create_account']->createFundAccount(
                $this->id,
                $this->type,
                $this->product);

            $this->trace->info(
                TraceCode::FTS_CREATE_ACCOUNT_COMPLETE,
                $ftsResponse);
        }
        catch (\Throwable $e)
        {
            $data = [
                'id'      => $this->id,
                'type'    => $this->type,
                'product' => $this->product,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTS_CREATE_ACCOUNT_FAILED,
                $data);

            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                return;
            }
        }
    }
}
