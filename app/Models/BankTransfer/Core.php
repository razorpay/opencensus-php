<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input)
    {
        // This method does not save to DB. It should not save to DB,
        // because it is used to validate-and-modify the input received
        // in the notify request. We only create a bank_transfer obj here.
        $bankTransfer = (new Entity)->build($input);

        return $bankTransfer;
    }

    public function process(array $input)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESSING,
            $input
        );

        try
        {
            $bankTransfer = $this->create($input);

            $this->mutex->acquireAndRelease(
                $input[Entity::PAYEE_ACCOUNT],
                function() use ($bankTransfer)
                {
                    (new Processor)->process($bankTransfer);
                },
                60,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

            $valid = true;
        }
        catch (Exception\BadRequestValidationFailureException $ex)
        {
            // Returning anything other than a 200 causes Kotak to retry here.
            //
            // However, validation failures are due to Kotak sending the request
            // in wrong format, or (more frequently) the wrong request altogether.
            // So retrying doesn't help us, and will cause unnecessary errors.
            // Best to trace, and return false, to stop the request.
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $input);

            $valid = false;
        }

        return $valid;
    }

    public function refund(array $data, Merchant\Entity $merchant)
    {
        return (new Refund)->process($data, $merchant);
    }

    public function notify(array $input)
    {
        // Bank Transfer core does not save to DB in this step.
        // This is effectively just a modify-and-validate.
        $this->create($input);

        $bankTransfer = $this->repo->bank_transfer->findByUtr($input[Entity::REQ_UTR]);

        if ($bankTransfer !== null)
        {
            $this->notifyIfApplicable($bankTransfer);
        }
        else
        {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_UNEXPECTED_NOTIFY,
                [
                    'input' => $input,
                ]
            );
        }

        return true;
    }

    protected function notifyIfApplicable(Entity $bankTransfer)
    {
        if ($bankTransfer->isNotified() === false)
        {
            $bankTransfer->setNotified(true);

            $this->repo->saveOrFail($bankTransfer);
        }
    }
}
