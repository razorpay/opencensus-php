<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Beneficiary;

class BeneficiaryRegistrationJob extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;

    const RETRY_INTERVAL       = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_transactions';

    /**
     * @var array
     */
    protected $channel;

    /**
     * @var string
     */
    protected $bankAccountId;

    public function __construct(string $mode, string $channel, string $bankAccountId)
    {
        parent::__construct($mode);

        $this->channel        = $channel;

        $this->bankAccountId  = $bankAccountId;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            if (in_array($this->channel, Channel::getChannelsWithOnlineBeneficiaryRegistration(), true) === false)
            {
                return;
            }

            $this->traceData(TraceCode::ATTEMPTING_BENEFICIARY_REGISTRATION);

            $bankAccount = $this->repoManager->bank_account->getBankAccountById($this->bankAccountId);

            if ($bankAccount === null)
            {
                $this->traceData(TraceCode::INVALID_BENEFICIARY_BANK_ACCOUNT_ID);

                return;
            }

            $status = (new Beneficiary)->registerBeneficiaryThroughApi($bankAccount, $this->channel);

            $this->traceData(
                TraceCode::BENEFICIARY_REGISTRATION_ATTEMPT_STATUS,
                [
                    'status'=> $status
                ]);
        }
        catch (LogicException $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BENEFICIARY_REGISTRATION_ATTEMPT_FAILED,
                [
                    'channel'         => $this->channel,
                    'attempt_count'   => $this->attempts(),
                    'bank_account_id' => $this->bankAccountId,
                ]);

            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(self::RETRY_INTERVAL);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BENEFICIARY_REGISTRATION_PROCESS_FAILED,
                [
                    'channel'             => $this->channel,
                    'attempt_count'       => $this->attempts(),
                    'bank_account_id'     => $this->bankAccountId,
                ]);

            $this->delete();
        }
    }

    protected function traceData(string $traceCode, array $extraData = [])
    {
        $this->trace->info(
            $traceCode,
            [
                'channel'         => $this->channel,
                'attempt_count'   => $this->attempts(),
                'bank_account_id' => $this->bankAccountId,
            ] + $extraData
        );
    }
}
