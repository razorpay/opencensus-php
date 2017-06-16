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

        $this->validateProviderIp($this->provider, $this->ip);

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

        $this->validateProviderIp($this->provider, $this->ip);

        $this->validator->validateInput('create', $input);

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

    protected function validateProviderIp(string $provider, string $ip)
    {
        if (Provider::validateIp($provider, $ip) === false)
        {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_IP_VALIDATION_FAILED,
                [
                    'provider' => $provider,
                    'ip'       => $ip,
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }
}
