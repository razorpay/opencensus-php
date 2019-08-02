<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Type;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\BankAccount\Beneficiary;

class BeneficiaryRegistration extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;

    const RETRY_INTERVAL       = 60;

    /**
     * @var string
     */
    protected $queueConfigKey = 'beneficiary_registrations';

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

            // TODO: Check when this can be empty
            // Example case: BcqrSOKTFuw1pS
            // Mode sent was live, but it was created in test mode.
            // No live bank account exists for the merchant: BcqrSKvM8bIq2g
            if (empty($bankAccount) === true)
            {
                $this->traceData(TraceCode::BANK_ACCOUNT_NOT_FOUND_FOR_BENE_REG);

                return;
            }

            // Check to avoid unnecessary tries.
            // As the `registerBeneficiaryThroughApi` checks for the type
            // and returns false for the bank account which are not `merchant` or `contact`
            if (in_array($bankAccount->getType(), Type::getBeneficiaryRegistrationTypes(), true) === false)
            {
                return;
            }

            $status = (new Beneficiary)->registerBeneficiaryThroughApi($bankAccount, $this->channel);

            // If Beneficiary Registration is successful dispatch it for Verification
            // Else Method registerBeneficiaryThroughApi throws a logic exception which
            // gets handled by catch block below.
            if ($status === true)
            {
                (new Beneficiary)->dispatchBankAccountForBeneficiaryVerification($bankAccount, $this->channel);
            }

            $this->traceData(
                TraceCode::BENEFICIARY_REGISTRATION_ATTEMPT_STATUS,
                [
                    'status'          => $status,
                    'bank_account_id' => $bankAccount->getId()
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

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->traceData(TraceCode::BENEFICIARY_REGISTRATION_PROCESS_RETRY);

                $this->release(self::RETRY_INTERVAL);
            }
            else
            {
                (new Beneficiary)->removeBeneficiaryRegistrationCacheKey($this->bankAccountId);
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
        }
        finally
        {
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
                'mode'            => $this->mode,
            ] + $extraData
        );
    }
}
