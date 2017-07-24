<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount\Provider;

class Service extends Base\Service
{
    protected $validator;
    protected $processor;
    protected $provider;
    protected $ip;
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->processor = new Processor;

        $this->provider = $this->auth->getInternalApp();

        $this->ip = $this->app['request']->getRealClientIp();

        $this->mutex = $this->app['api.mutex'];
    }

    public function process(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            $input
        );

        $this->validateProvider();

        try
        {
            $this->mutex->acquireAndRelease(
                $input[Entity::PAYEE_ACCOUNT],
                function() use ($input)
                {
                    $bankTransfer = $this->processor->process($input);
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

        $data = [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $input[Entity::REQ_UTR],
        ];

        return $data;
    }

    public function notify(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_NOTIFY_REQUEST,
            $input
        );

        $this->validateProvider();

        // Bank Transfer core does not save to DB in this step.
        // This is effectively just a modify-and-validate.
        $this->core->create($input);

        $bankTransfer = $this->repo->bank_transfer->findByUtr($input[Entity::REQ_UTR]);

        if ($bankTransfer !== null)
        {
            $this->core->notify($bankTransfer);
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

        return [
            'success'        => true,
            'message'        => null,
            'transaction_id' => $input[Entity::REQ_UTR],
        ];
    }

    public function fetchBankTransferForPayment(string $paymentId)
    {
        $payment = $this->repo
                        ->payment
                        ->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByPayment($payment);

        return $bankTransfer->toArrayPublic();
    }

    protected function validateProvider()
    {
        if ((Provider::validateMode($this->provider, $this->mode) === false) or
            (Provider::validateIp($this->provider, $this->ip) === false))
        {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_PROVIDER_VALIDATION_FAILED,
                [
                    'provider' => $this->provider,
                    'ip'       => $this->ip,
                    'mode'     => $this->mode,
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }
}
