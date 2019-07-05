<?php

namespace RZP\Models\BankAccount;

use App;
use Mail;
use Cache;
use Config;
use Carbon\Carbon;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Jobs\FTS\RegisterAccount;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\BeneficiaryRegistration;
use RZP\Jobs\BeneficiaryVerification;
use RZP\Models\NodalBeneficiary\Status;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Settlement\SlackNotification;

class Beneficiary extends Base\Core
{
    public function register(array $input, string $channel): array
    {
        (new Validator)->validateInput('merchant_beneficiary_register', $input);

        $merchantIds = $input['merchant_ids'] ?? [];

        $bankAccounts = (new BankAccount\Repository)->getAllActivatedMerchantAccountsOrderedByCreatedAt($merchantIds);

        $result = $this->registerBeneficiary($bankAccounts, $channel);

        return $result;
    }

    /**
     * Enqueues the bank account in queue to perform beneficiary registration
     * This will enqueue different message for each channel
     *
     * @param Entity $bankAccount
     */
    public function enqueueForBeneficiaryRegistration(Entity $bankAccount)
    {
        $isValidType = Type::isValidBeneficiaryRegistrationType($bankAccount->getType());

        // We don't have to register beneficiary for the bank account created in test mode.
        if (($this->mode === Mode::TEST) or ($isValidType === false))
        {
            return;
        }

        // We enqueue bank account with all the available channels which provide API based bene registration.
        $channels = Channel::getChannelsWithOnlineBeneficiaryRegistration();

        foreach ($channels as $channel)
        {
            $this->dispatchBankAccountForBeneficiaryRegistration($bankAccount, $channel);
        }
    }

    /**
     * Push the bank account id to the queue along with the channel on which bene registration
     * has to be performed. Also suppresses error which might happen because of queue
     *
     * @param Entity $bankAccount
     * @param string $channel
     * @return bool
     */
    public function dispatchBankAccountForBeneficiaryRegistration(Entity $bankAccount, string $channel)
    {
        $cacheKey = 'beneficiary_registration:'.$bankAccount->getId();

        try
        {
            if (Cache::has($cacheKey) === true)
            {
                return ;
            }

            Cache::put($cacheKey, 'in_progress');

            BeneficiaryRegistration::dispatch($this->mode, $channel, $bankAccount->getId());

            $this->trace->info(
                TraceCode::BANK_ACCOUNT_ENQUEUED_FOR_REGISTRATION,
                [
                    'mode'            => $this->mode,
                    'channel'         => $channel,
                    'bank_account_id' => $bankAccount->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $cacheValue = Cache::pull($cacheKey);

            $this->trace->info(
                TraceCode::BENEFICIARY_REGISTRATION_REDIS_KEY_REMOVED,
                [
                    'key'           => $cacheKey,
                    'value'         => $cacheValue,
                ]);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_BANK_ACCOUNT,
                [
                    'mode'            => $this->mode,
                    'channel'         => $channel,
                    'bank_account_id' => $bankAccount->getId(),
                ]);
        }
    }

    public function dispatchBankAccountForBeneficiaryVerification(Entity $bankAccount, string $channel)
    {
        $cacheKey = 'beneficiary_verification:'.$bankAccount->getId();

        try
        {
            if (Cache::has($cacheKey) === true)
            {
                return;
            }

            Cache::put($cacheKey, 'in_progress');

            BeneficiaryVerification::dispatch($this->mode, $channel, $bankAccount->getId());

            $this->trace->info(
                TraceCode::BANK_ACCOUNT_ENQUEUED_FOR_VERIFY,
                [
                    'mode'            => $this->mode,
                    'channel'         => $channel,
                    'bank_account_id' => $bankAccount->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $cacheValue = Cache::pull($cacheKey);

            $this->trace->info(
                TraceCode::BENEFICIARY_VERIFICATION_REDIS_KEY_REMOVED,
                [
                    'key'           => $cacheKey,
                    'value'         => $cacheValue,
                ]);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_BANK_ACCOUNT_VERIFICATION
            );
        }
    }

    public function registerBetweenTimestamps(array $input, string $channel): array
    {
        (new Validator)->validateInput('beneficiary_register', $input);

        $to = Carbon::today(Timezone::IST);

        if ((isset($input[Entity::FROM]) === true) and (isset($input[Entity::TO]) === true))
        {
            $to   = Carbon::createFromTimestamp($input[Entity::TO], Timezone::IST);
            $from = Carbon::createFromTimestamp($input[Entity::FROM], Timezone::IST);
        }
        else
        {
            if (isset($input[Entity::ON]) === true)
            {
                $to = Carbon::createFromTimestamp($input[Entity::ON], Timezone::IST);
            }

            if (Holidays::isWorkingDay($to) === false)
            {
                return ['message' => 'Today is a holiday! Happy holidays :)'];
            }

            $from = Holidays::getPreviousWorkingDay($to);
        }

        $bankAccounts = $this->repo->bank_account->getMerchantBankAccountsBetweenTimestamp(
            $from->getTimestamp(),
            $to->getTimestamp());

        if ($bankAccounts->count() === 0)
        {
            return ['message' => 'No Beneficiary added since last report.'];
        }

        $newBeneficiaryCount = $bankAccounts->count();

        $this->trace->info(
            TraceCode::MERCHANT_BENEFICIARY_FILE_GENERATE,
            ['new_beneficiaries_added' => $newBeneficiaryCount]);

        $result = $this->registerBeneficiary($bankAccounts, $channel, $input);

        // should notify after beneficiary file is generated.
        $message = 'Merchant Beneficiary file generated. Beneficiary added since'.
            ' last report is '. $newBeneficiaryCount;

        (new SlackNotification)->send($message, ['channel' => $channel]);

        return $result;
    }

    public function registerBeneficiary(
        Base\PublicCollection $bankAccounts,
        string $channel,
        array $input = []): array
    {
        $beneClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\Beneficiary';

        $response = (new $beneClass)->register($bankAccounts, $input);

        return $response;
    }

    public function verifyBeneficiary(
        Base\PublicCollection $bankAccounts,
        string $channel,
        array $input = []): array
    {
        $beneClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\Beneficiary';

        $response = (new $beneClass)->verify($bankAccounts, $input);

        return $response;
    }

    /**
     * Used to register beneficiary added in last n minutes.
     * Here, 'n' is the value obtained from key 'duration'
     *
     * @param array $input
     * @param string $channel
     * @return array
     * @throws InvalidArgumentException
     */
    public function registerBeneficiariesThroughApi(array $input, string $channel): array
    {
        $this->trace->info(
            TraceCode::BENEFICIARY_REGISTER_API_INIT,
            [
                'input'   => $input,
                'channel' => $channel
            ]);

        (new Validator)->validateInput('beneficiary_register_api', $input);

        if ((array_key_exists('all', $input) === true) and ($input['all'] === true))
        {
            $bankAccounts = $this->fetchNonRegisteredBankAccount($channel);
        }
        else if (array_key_exists('duration', $input) === true)
        {
            $bankAccounts = $this->fetchBankAccountBetweenTimestamps($input['duration']);
        }
        else
        {
            throw new InvalidArgumentException('Input key all or duration not specified');
        }

        if ($bankAccounts->count() === 0)
        {
            return ['message' => 'No Beneficiary added since last report.'];
        }

        //TODO:: Disabled fts flow for bene registration.
        /*$redis = $this->app['redis']->connection();

        $ftsChannels = $redis->SMEMBERS(ConfigKey::FTS_CHANNELS);

        if(in_array($channel, $ftsChannels, true) === true)
        {
            $result = $this->registerBeneficiaryThroughFTS($bankAccounts, $channel);
        }*/

        $result = $this->registerBeneficiary($bankAccounts, $channel, $input);

        $beneficiaryCount = $bankAccounts->count();

        $message = 'Merchant Beneficiary api executed. Beneficiary added since '.
                   'last report is '. $beneficiaryCount;

        (new SlackNotification)->send($message, ['channel' => $channel]);

        return $result;
    }

    /**
     * @param Entity $bankAccount
     * @param string $channel
     *
     * @return bool
     * @throws LogicException
     */
    public function registerBeneficiaryThroughApi(Entity $bankAccount, string $channel)
    {
        if (in_array($bankAccount->getType(), Type::getBeneficiaryRegistrationTypes(), true) === false)
        {
            return false;
        }

        $bankAccounts = (new PublicCollection)->push($bankAccount);

        $this->registerBeneficiary($bankAccounts, $channel);

        $status = $this->checkBeneficiaryRegistrationStatus($bankAccount, $channel);

        if ($status === false)
        {
            throw new LogicException(
                'Beneficiary registration failed',
                null,
                [
                    'channel'         => $channel,
                    'bank_account_id' => $bankAccount->getId(),
                ]);
        }

        return $status;
    }

    /**
     * @param Entity $bankAccount
     * @param string $channel
     *
     * @return bool
     * @throws LogicException
     */
    public function verifyBeneficiaryThroughApi(Entity $bankAccount, string $channel)
    {
        $status = $this->checkBeneficiaryRegistrationStatus($bankAccount, $channel);

        // Beneficiary verification failed, since beneficiary is not registered yet
        if ($status === false)
        {
            return $status;
        }

        $bankAccounts = (new PublicCollection)->push($bankAccount);

        $this->verifyBeneficiary($bankAccounts, $channel);

        $status = $this->checkBeneficiaryVerificationStatus($bankAccount, $channel);

        if ($status === false)
        {
            throw new LogicException(
                'Beneficiary verification failed',
                null,
                [
                    'channel'         => $channel,
                    'bank_account_id' => $bankAccount->getId(),
                ]);
        }

        return $status;
    }

    /**
     * @param int $duration
     * @return Base\PublicCollection
     */
    protected function fetchBankAccountBetweenTimestamps(int $duration): Base\PublicCollection
    {
        $timeNow = Carbon::now(Timezone::IST);

        $endTime   = $timeNow->getTimestamp();

        $startTime = $timeNow->subSeconds($duration)->getTimestamp();

        $this->trace->info(
            TraceCode::BENEFICIARY_REGISTER_API_FETCH,
            [
                'from' => $startTime,
                'to'   => $endTime
            ]);

        return $this->repo->bank_account->getMerchantBankAccountsBetweenTimestamp($startTime, $endTime);
    }

    /**
     * @param $channel
     *
     * @return Base\PublicCollection
     */
    protected function fetchNonRegisteredBankAccount($channel): Base\PublicCollection
    {
        $bankAccounts = $this->repo->nodal_beneficiary->fetchNonRegisteredBankAccount($channel);

        if (empty($bankAccounts) === true)
        {
            return new Base\PublicCollection;
        }

        return $this->repo->bank_account->findMany($bankAccounts);
    }

    /**
     * @param $bankAccount
     * @param $channel
     * @return bool
     */
    public function checkBeneficiaryRegistrationStatus($bankAccount, $channel): bool
    {
        $nodalBeneficiary = $this->repo
                                 ->nodal_beneficiary
                                 ->fetchActivatedBeneficiaryDetailsForChannel(
                                     $bankAccount->getId(),
                                     $channel
                                 );

        if ($nodalBeneficiary === null)
        {
            return false;
        }

        $registrationStatus = $nodalBeneficiary->getRegistrationStatus();

        if ($registrationStatus === Status::REGISTERED)
        {
            return true;
        }

        return false;
    }

    /**
     * @param $bankAccount
     * @param $channel
     * @return bool
     */
    public function checkBeneficiaryVerificationStatus($bankAccount, $channel): bool
    {
        $nodalBeneficiary = $this->repo
                                 ->nodal_beneficiary
                                 ->fetchActivatedBeneficiaryDetailsForChannel(
                                     $bankAccount->getId(),
                                     $channel
                                 );

        if ($nodalBeneficiary === null)
        {
            return false;
        }

        $registrationStatus = $nodalBeneficiary->getRegistrationStatus();

        if ($registrationStatus === Status::VERIFIED)
        {
            return true;
        }

        return false;
    }

    public function getBeneficiaryStatus($bankAccount, $channel)
    {
        $nodalBeneficiary = $this->repo
                                 ->nodal_beneficiary
                                 ->fetchActivatedBeneficiaryDetailsForChannel(
                                     $bankAccount->getId(),
                                     $channel
                            );
        if (empty($nodalBeneficiary) === true)
        {
            return null;
        }

        return $nodalBeneficiary->getRegistrationStatus();
    }

    /**
     * Method to call FTS for Beneficiary Registration
     *
     * @param $bankAccounts
     * @param $channel
     * @return response from FTS
     */
    public function registerBeneficiaryThroughFTS(PublicCollection $bankAccounts, $channel):array
    {
        $ftsAccountIds = [];

        try
        {
            foreach ($bankAccounts as $ba) {
                $ftsAccountIds[] = $ba->getFtsFundAccountId();
            }

            RegisterAccount::dispatch($this->mode, $channel, $ftsAccountIds);

            $this->trace->info(
                TraceCode::FTS_REGISTER_ACCOUNT_JOB_DISPATCHED,
                [
                    'channel'         => $channel,
                    'fts_account_ids' => $ftsAccountIds,
                ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_REGISTER_ACCOUNT_DISPATCH_FAILED,
                [
                    'channel'         => $channel,
                    'fts_account_ids' => $ftsAccountIds,
                ]);
        }

        return [
            'status' => 'Request dispatched to fts',
        ];
    }
}
