<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount\Provider;

class Service extends Base\Service
{
    protected $validator;
    protected $processor;
    protected $provider;
    protected $ip;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->processor = new Processor;

        $this->provider = $this->auth->getInternalApp();

        $this->ip = $this->app['request']->getRealClientIp();
    }

    public function process(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            $input
        );

        $this->validateProvider();

        $bankTransfer = $this->processor->process($input);

        $data = [
            'valid'          => true,
            'message'        => null,
            'transaction_id' => $bankTransfer->getUtr(),
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
