<?php

namespace RZP\Jobs;

use RZP\Models\Merchant;
use Throwable;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;


class PushProvisioningTokenCreateJob extends Job
{

    protected $queueConfigKey = 'pushprovisioning';

    protected $createTokenInput;

    protected $merchant;

    protected $asyncTokenisationJobId;

    /**
     * @var Token\Service
     */
    protected $tokenService;

    public function __construct(string $mode, array $createTokenInput, Merchant\Entity $merchant, string $asyncTokenisationJobId)
    {
        parent::__construct($mode);

        $this->createTokenInput = $createTokenInput;

        $this->merchant = $merchant;

        $this->asyncTokenisationJobId = $asyncTokenisationJobId;
    }

    public function init(): void
    {
        parent::init();

        $this->tokenService = new Token\Service();
    }

    /**
     * Process queue request
     */
    public function handle(): void
    {
        parent::handle();

        try
        {

            $startTime = millitime();

            $this->trace->info(TraceCode::PUSH_PROVISIONING_TOKEN_CREATE_JOB_REQUEST, [
                'customerId'               => $this->createTokenInput['customer_id'],
                'merchantId'               => $this->merchant->getPublicId(),
                'asyncTokenisationJobId' => $this->asyncTokenisationJobId
            ]);

            $this->tokenService -> createNetworkToken($this->createTokenInput, $this->merchant);

            $this->trace->info(TraceCode::PUSH_PROVISIONING_TOKEN_CREATE_JOB_SUCCESS, [
                'customerId'               => $this->createTokenInput['customer_id'],
                'merchantId'               => $this->merchant->getPublicId(),
                'asyncTokenisationJobId' => $this->asyncTokenisationJobId,
                'startTime' => $startTime,
                'timeTaken'                 => millitime() - $startTime
            ]);

            $this->delete();

            return;
        }
        catch (Throwable $e)
        {

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PUSH_PROVISIONING_TOKEN_CREATE_JOB_ERROR,
                [
                    'mode'                   => $this->mode,
                    'customerId'               => $this->createTokenInput['customer_id'],
                    'merchantId'               => $this->merchant->getPublicId(),
                    'asyncTokenisationJobId' => $this->asyncTokenisationJobId
                ]
            );
        }
    }

}
