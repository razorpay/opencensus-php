<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Provider;

class Service extends Base\Service
{
    protected $validator;
    protected $provider;
    protected $ip;
    protected $mutex;
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->provider = $this->auth->getInternalApp();

        $this->ip = $this->app['request']->ip();
    }

    public function process(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            $input
        );

        $this->validateProvider();

        $valid = $this->core->process($input);

        return [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $input[Entity::REQ_UTR],
        ];
    }

    public function notify(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_NOTIFY_REQUEST,
            $input
        );

        $this->validateProvider();

        $success = $this->core->notify($input);

        return [
            'success'        => $success,
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
