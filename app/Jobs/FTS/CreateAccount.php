<?php

namespace RZP\Jobs\FTS;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Exception\RecordAlreadyExists;
use RZP\Models\Settlement\SlackNotification;

class CreateAccount extends Job
{
    const RETRY_PERIOD         = 30;

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

    protected $status;

    /**
     * @var int
     */
    public $timeout = 60;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_create_account';

    public function __construct(
        string $mode,
        string $id,
        string $type,
        string $product,
        string $status=null)
    {
        parent::__construct($mode);

        $this->id   = $id;

        $this->type = $type;

        $this->product = $product;

        $this->status = $status;
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

            $accountService = App::getFacadeRoot()['fts_create_account'];

            $accountService->initialize($this->id, $this->type, $this->product, $this->status);

            if (empty($accountService->getAccount()) === true)
            {
                $this->trace->info(TraceCode::FTS_CREATE_ACCOUNT_INVALID_ID,
                    [
                        'id'      => $this->id,
                        'type'    => $this->type,
                    ]);

                $this->delete();

                return;
            }

            $isAccountCreatedInFts = $accountService->isAccountCreatedInFts();

            if ($isAccountCreatedInFts === true)
            {
                $this->trace->info(TraceCode::FTS_CREATE_ACCOUNT_DUPLICATE,
                    [
                        'id'      => $this->id,
                        'type'    => $this->type,
                        'product' => $this->product,
                    ]);

                $this->delete();

                return;
            }

            $ftsResponse = $accountService->createFundAccount();

            $this->trace->info(
                TraceCode::FTS_CREATE_ACCOUNT_COMPLETE,
                [
                    "id"           => $this->id,
                    "type"         => $this->type,
                    "product"      => $this->product,
                    "status"       => $this->status,
                    "fts_response" => $ftsResponse,
                ]);
        }
        catch (RecordAlreadyExists $e)
        {
            $this->delete();
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

                $operation = 'fts create account job failed';

                (new SlackNotification)->send($operation, $data, null, 1, 'fts_alerts');

                return;
            }
            else
            {
                $this->release(self::RETRY_PERIOD);
            }
        }
    }
}
