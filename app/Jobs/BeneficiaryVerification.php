<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Type;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Beneficiary;

class BeneficiaryVerification extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 45;

    const RETRY_INTERVAL       = 4;

    /**
     * @var string
     */
    protected $queueConfigKey = 'beneficiary_verifications';

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

            if (in_array($this->channel, Channel::getChannelsWithOnlineBeneficiaryVerification(), true) === false)
            {
                return;
            }

            $this->traceData(TraceCode::ATTEMPTING_BENEFICIARY_VERIFICATION);

            $bankAccount = $this->repoManager->bank_account->getBankAccountById($this->bankAccountId);

            // TODO: Check when this can be empty
            // Example case: BcqrSOKTFuw1pS
            // Mode sent was live, but it was created in test mode.
            // No live bank account exists for the merchant: BcqrSKvM8bIq2g
            if (empty($bankAccount) === true)
            {
                $this->traceData(TraceCode::BANK_ACCOUNT_NOT_FOUND_FOR_BENE_VERIFY);

                return;
            }

            // Check to avoid unnecessary tries.
            // checks for the type and returns false for the bank account which are not `merchant` or `contact`
            if (in_array($bankAccount->getType(), Type::getBeneficiaryRegistrationTypes(), true) === false)
            {
                return;
            }

            $status = (new Beneficiary)->verifyBeneficiaryThroughApi($bankAccount, $this->channel);

            $this->traceData(
                TraceCode::BENEFICIARY_VERIFY_ATTEMPT_STATUS,
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
                TraceCode::BENEFICIARY_VERIFY_ATTEMPT_FAILED,
                [
                    'channel'         => $this->channel,
                    'attempt_count'   => $this->attempts(),
                    'bank_account_id' => $this->bankAccountId,
                ]);

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->traceData(TraceCode::BENEFICIARY_VERIFY_PROCESS_RETRY);

                $this->release(self::RETRY_INTERVAL);

                return;
            }
            else
            {
                (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->bankAccountId);

                $this->delete();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BENEFICIARY_VERIFY_PROCESS_FAILED,
                [
                    'channel'             => $this->channel,
                    'attempt_count'       => $this->attempts(),
                    'bank_account_id'     => $this->bankAccountId,
                ]);

            (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->bankAccountId);

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
