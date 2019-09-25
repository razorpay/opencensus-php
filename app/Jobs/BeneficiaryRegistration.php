<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Type;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Models\FundAccount\Type as FundAccountType;

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
     * Account ID can have bank account Id / card Id
     *
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

            if (in_array($this->channel, Channel::getChannelsWithOnlineBeneficiaryRegistration(), true) === false)
            {
                (new Beneficiary)->removeBeneficiaryRegistrationCacheKey($this->accountId);

                return;
            }

            $this->traceData(TraceCode::ATTEMPTING_BENEFICIARY_REGISTRATION);

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
                (new Beneficiary)->removeBeneficiaryRegistrationCacheKey($this->accountId);

                $this->traceData(TraceCode::ACCOUNT_NOT_FOUND_FOR_BENE_REG);

                return;
            }

            // Check to avoid unnecessary tries.
            // As the `registerBeneficiaryThroughApi` checks for the type
            // and returns false for the bank account which are not `merchant` or `contact`
            if (($this->accountType === FundAccountType::BANK_ACCOUNT) and
                (in_array($accountEntity->getType(), Type::getBeneficiaryRegistrationTypes(), true) === false))
            {
                (new Beneficiary)->removeBeneficiaryRegistrationCacheKey($this->accountId);

                return;
            }

            $status = (new Beneficiary)->registerBeneficiaryThroughApi($accountEntity, $this->accountType, $this->channel);

            // If Beneficiary Registration is successful dispatch it for Verification
            // Else Method registerBeneficiaryThroughApi throws a logic exception which
            // gets handled by catch block below.
            if ($status === true)
            {
                (new Beneficiary)->dispatchBankAccountForBeneficiaryVerification(
                    $accountEntity,
                    $this->channel,
                    $this->accountType);
            }

            $this->traceData(
                TraceCode::BENEFICIARY_REGISTRATION_ATTEMPT_STATUS,
                [
                    'status'       => $status,
                    'account_id'   => $this->accountId,
                    'account_type' => $this->accountType,
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
                    'account_id'      => $this->accountId,
                    'account_type'    => $this->accountType,
                    'attempt_count'   => $this->attempts(),
                ]);

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->traceData(TraceCode::BENEFICIARY_REGISTRATION_PROCESS_RETRY);

                $this->trace->count(
                    TraceCode::BENEFICIARY_REGISTRATION_PROCESS_RETRY,
                    [
                        'channel' => $this->channel,
                        'mode'    => $this->mode,
                    ]);

                $this->release(self::RETRY_INTERVAL);

                return;
            }
            else
            {
                (new Beneficiary)->removeBeneficiaryRegistrationCacheKey($this->accountId);

                $this->delete();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BENEFICIARY_REGISTRATION_PROCESS_FAILED,
                [
                    'channel'         => $this->channel,
                    'account_id'      => $this->accountId,
                    'account_type'    => $this->accountType,
                    'attempt_count'   => $this->attempts(),
                ]);

            (new Beneficiary)->removeBeneficiaryRegistrationCacheKey($this->accountId);

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
