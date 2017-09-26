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
    protected $provider;
    protected $ip;
    protected $mutex;
    protected $core;

    /**
     * Service constructor. Sets provider from app auth, and
     * sets request IP for use in validation of providers.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->provider = $this->auth->getInternalApp();

        $this->ip = $this->app['request']->ip();
    }

    /**
     * Entry point for Kotak or other providers. Response contains
     * UTR because it was requested, no idea how it's useful.
     *
     * @param array $input
     *
     * @return array
     */
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

    /**
     * Kotak has a second route that it hits to notify us of a bank transfer payment.
     * It was useful when these APIs were being planned, but serves no real purpose now.
     *
     * @param array $input
     *
     * @return array
     */
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

    /**
     * This is used by the payment_bank_transfer_fetch route. Bank transfer
     * public entity contains payer bank account info for use by the merchant.
     *
     * @param string $paymentId
     *
     * @return array
     */
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

    /**
     * An IP check is performed to ensure requests are coming from whitelisted IPs.
     *
     * @throws Exception\BadRequestException
     */
    protected function validateProvider()
    {
        if (Provider::validateIp($this->provider, $this->ip) === false)
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
