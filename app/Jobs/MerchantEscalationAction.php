<?php


namespace RZP\Jobs;


use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Escalations\Actions;

class MerchantEscalationAction extends Job
{
    protected $merchantId;

    protected $actionId;

    protected $params;

    protected $handlerClazz;

    public function __construct(string $merchantId, string $actionId, string $handlerClazz, array $params = [])
    {
        parent::__construct('live');

        $this->merchantId = $merchantId;

        $this->actionId = $actionId;

        $this->params = $params;

        $this->handlerClazz = $handlerClazz;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->debug(TraceCode::ESCALATION_ACTION_JOB, [
            'merchant_id'   => $this->merchantId,
            'action_id' => $this->actionId
        ]);

        $this->mutex->acquireAndRelease(
            $this->merchantId,
            function() {

                (new Actions\Core)->handleAction(
                    $this->merchantId,
                    $this->actionId,
                    $this->handlerClazz,
                    $this->params
                );

                $this->delete();
            },
            Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Constants::MERCHANT_MUTEX_RETRY_COUNT);
    }
}
