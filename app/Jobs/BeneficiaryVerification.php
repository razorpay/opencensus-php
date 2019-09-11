<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Type;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Models\FundAccount\Type as FundAccountType;

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
    protected $accountId;

    /**
     * @var string
     */
    protected $accountType;

    public function __construct(string $mode, string $channel, string $accountId, string $accountType)
    {
        parent::__construct($mode);

        $this->channel     = $channel;

        $this->accountId   = $accountId;

        $this->accountType = $accountType;
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
                (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->accountId);

                return;
            }

            $this->traceData(TraceCode::ATTEMPTING_BENEFICIARY_VERIFICATION);

            $accountEntity = null;

            if ($this->accountType === FundAccountType::BANK_ACCOUNT)
            {
                $accountEntity = $this->repoManager->bank_account->getBankAccountById($this->accountId);
            }
            else if ($this->accountType === FundAccountType::CARD)
            {
                $accountEntity = $this->repoManager->card->getCardById($this->accountId);
            }

            // TODO: Check when this can be empty
            // Example case: BcqrSOKTFuw1pS
            // Mode sent was live, but it was created in test mode.
            // No live bank account exists for the merchant: BcqrSKvM8bIq2g
            if (empty($accountEntity) === true)
            {
                (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->accountId);

                $this->traceData(TraceCode::ACCOUNT_NOT_FOUND_FOR_BENE_VERIFY);

                return;
            }

            // Check to avoid unnecessary tries.
            // checks for the type and returns false for the bank account which are not `merchant` or `contact`
            if (($this->accountType === FundAccountType::BANK_ACCOUNT) and
                (in_array($accountEntity->getType(), Type::getBeneficiaryRegistrationTypes(), true) === false))
            {
                (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->accountId);

                return;
            }

            $status = (new Beneficiary)->verifyBeneficiaryThroughApi($accountEntity, $this->accountType, $this->channel);

            $this->traceData(
                TraceCode::BENEFICIARY_VERIFY_ATTEMPT_STATUS,
                [
                    'status'     => $status,
                    'account_id'   => $this->accountId,
                    'account_type' => $this->accountType,
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
                    'account_id'      => $this->accountId,
                    'account_type'    => $this->accountType,
                    'attempt_count'   => $this->attempts(),
                ]);

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->traceData(TraceCode::BENEFICIARY_VERIFY_PROCESS_RETRY);

                $this->trace->count(
                    TraceCode::BENEFICIARY_VERIFY_PROCESS_RETRY,
                    [
                        'channel' => $this->channel,
                        'mode'    => $this->mode,
                    ]);

                $this->release(self::RETRY_INTERVAL);

                return;
            }
            else
            {
                (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->accountId);

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
                    'channel'         => $this->channel,
                    'account_id'      => $this->accountId,
                    'account_type'    => $this->accountType,
                    'attempt_count'   => $this->attempts(),
                ]);

            (new Beneficiary)->removeBeneficiaryVerificationCacheKey($this->accountId);

            $this->delete();
        }
    }

    protected function traceData(string $traceCode, array $extraData = [])
    {
        $this->trace->info(
            $traceCode,
            [
                'mode'            => $this->mode,
                'channel'         => $this->channel,
                'account_id'      => $this->accountId,
                'account_type'    => $this->accountType,
                'attempt_count'   => $this->attempts(),
            ] + $extraData
        );
    }
}
