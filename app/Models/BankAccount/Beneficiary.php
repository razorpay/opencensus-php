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
use RZP\Models\FundAccount\Type as FundAccountType;

class Beneficiary extends Base\Core
{
    const BENEFICIARY_CACHE_KEY_TTL = 5;

    public function register(array $input, string $channel): array
    {
        (new Validator)->validateInput('merchant_beneficiary_register', $input);

        $merchantIds = $input['merchant_ids'] ?? [];

        $bankAccounts = (new BankAccount\Repository)->getAllActivatedMerchantAccountsOrderedByCreatedAt($merchantIds);

        $result = $this->registerBeneficiary($bankAccounts, $channel);

        return $result;
    }

    /**
     * @param Base\Entity $account
     * @param $accountType
     * @return bool
     */
    public function isValidBeneficiaryRegistrationType(Base\Entity $account, $accountType): bool
    {
        if ($accountType === FundAccountType::BANK_ACCOUNT)
        {
            return Type::isValidBeneficiaryRegistrationType($account->getType());
        }
        else
        {
            return true;
        }
    }


    /**
     * Enqueues the bank account in queue to perform beneficiary registration
     * This will enqueue different message for each channel
     *
     * @param Base\Entity $account
     * @param string $accountType
     */
    public function enqueueForBeneficiaryRegistration(Base\Entity $account, $accountType = FundAccountType::BANK_ACCOUNT)
    {
        $traceData = [
            'account_type' => $accountType,
            'type' => $account->getType(),
        ];

        if (($accountType === FundAccountType::BANK_ACCOUNT) &&
            ($account->getType() === Type::CONTACT)) {

            $this->trace->info(TraceCode::FTS_BENEFICIARY_REGISTER_SKIPPED, $traceData);

            return;
        }

        $isValidType = $this->isValidBeneficiaryRegistrationType($account, $accountType);

        // We don't have to register beneficiary for the bank account created in test mode.
        // Enabled it for test cases.
        if (($this->app['env'] !== 'testing') and
            (($this->mode === Mode::TEST) or ($isValidType === false)))
        {
            return;
        }

        // We enqueue bank account with all the available channels which provide API based bene registration.
        $channels = Channel::getChannelsWithOnlineBeneficiaryRegistration();

        foreach ($channels as $channel)
        {
            $this->dispatchBankAccountForBeneficiaryRegistration($account, $channel, $accountType);
        }
    }

    /**
     * Push the bank account id / card id to the queue along with the channel on which bene registration
     * has to be performed. Also suppresses error which might happen because of queue
     *
     * @param Base\Entity $account
     * @param string $accountType
     * @param string $channel
     */
    public function dispatchBankAccountForBeneficiaryRegistration(Base\Entity $account,
                                                                  string $channel,
                                                                  $accountType = FundAccountType::BANK_ACCOUNT)
    {
        $cacheKey = ConfigKey::BENEFICIARY_REGISTRATION . $account->getId();

        $verifyCacheKey = ConfigKey::BENEFICIARY_VERIFICATION . $account->getId();

        $traceData = [
            'mode'         => $this->mode,
            'channel'      => $channel,
            'account_id'   => $account->getId(),
            'account_type' => $accountType,
        ];

        try
        {
            // Return if Already dispatched and in process.
            if ((Cache::has($cacheKey) === true) or
                (Cache::has($verifyCacheKey) === true))
            {
                $this->trace->info(TraceCode::BENEFICIARY_REGISTRATION_ALREADY_IN_PROGRESS, $traceData);

                return;
            }

            Cache::put($cacheKey, 'in_progress', self::BENEFICIARY_CACHE_KEY_TTL);

            BeneficiaryRegistration::dispatch($this->mode, $channel, $account->getId(), $accountType);

            $this->trace->info(TraceCode::ACCOUNT_ENQUEUED_FOR_REGISTRATION, $traceData);
        }
        catch (\Throwable $e)
        {
            $this->removeBeneficiaryRegistrationCacheKey($account->getId());

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_ACCOUNT_FOR_REGISTRATION,
                $traceData);
        }
    }

    /**
     * Push the bank account id to the queue along with the channel on which bene verification
     * has to be performed. Also suppresses error which might happen because of queue
     *
     * @param Base\Entity $account
     * @param $accountType
     * @param string $channel
     */
    public function dispatchBankAccountForBeneficiaryVerification(Base\Entity $account,
                                                                  string $channel,
                                                                  $accountType = FundAccountType::BANK_ACCOUNT)
    {
        $cacheKey = ConfigKey::BENEFICIARY_VERIFICATION . $account->getId();

        $traceData = [
            'mode'         => $this->mode,
            'channel'      => $channel,
            'account_id'   => $account->getId(),
            'account_type' => $accountType,
        ];

        try
        {
            // Return if Already dispatched and in process.
            if (Cache::has($cacheKey) === true)
            {
                $this->trace->info(TraceCode::BENEFICIARY_VERIFICATION_ALREADY_IN_PROGRESS, $traceData);

                return;
            }

            Cache::put($cacheKey, 'in_progress', self::BENEFICIARY_CACHE_KEY_TTL);

            BeneficiaryVerification::dispatch($this->mode, $channel, $account->getId(), $accountType);

            $this->trace->info(
                TraceCode::ACCOUNT_ENQUEUED_FOR_VERIFY, $traceData);
        }
        catch (\Throwable $e)
        {
            $this->removeBeneficiaryVerificationCacheKey($account->getId());

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_ACCOUNT_VERIFICATION,
                $traceData);
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

        $result = $this->registerBeneficiary($bankAccounts, $channel, FundAccountType::BANK_ACCOUNT, $input);

        // should notify after beneficiary file is generated.
        $message = 'Merchant Beneficiary file generated. Beneficiary added since'.
            ' last report is '. $newBeneficiaryCount;

        (new SlackNotification)->send($message, ['channel' => $channel]);

        return $result;
    }

    public function registerBeneficiary(
        Base\PublicCollection $accounts,
        string $channel,
        string $accountType = FundAccountType::BANK_ACCOUNT,
        array $input = []): array
    {
        $beneClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\Beneficiary';

        $response = (new $beneClass)->register($accounts, $accountType, $input);

        return $response;
    }

    /**
     * Invokes the respective method in Beneficary Class
     * for the channel and returns response
     * @param PublicCollection $bankAccounts
     * @param string $accountType
     * @param string $channel
     * @param array $input
     * @return array
     */
    public function verifyBeneficiary(
        Base\PublicCollection $bankAccounts,
        string $channel,
        string $accountType = FundAccountType::BANK_ACCOUNT,
        array $input = []): array
    {
        $beneClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\Beneficiary';

        $response = (new $beneClass)->verify($bankAccounts, $accountType, $input);

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

        $result = $this->registerBeneficiary($bankAccounts, $channel, FundAccountType::BANK_ACCOUNT, $input);

        $beneficiaryCount = $bankAccounts->count();

        $message = 'Merchant Beneficiary api executed. Beneficiary added since '.
                   'last report is '. $beneficiaryCount;

        (new SlackNotification)->send($message, ['channel' => $channel]);

        return $result;
    }

    /**
     * Registers Beneficiary through api based channels, If Registration status is false,
     * It throws logic exception else returns the status.
     *
     * @param Base\Entity $accountEntity
     * @param string $accountType
     * @param string $channel
     *
     * @return bool
     * @throws LogicException
     */
    public function registerBeneficiaryThroughApi(Base\Entity $accountEntity, string $accountType, string $channel)
    {
        $accounts = (new PublicCollection)->push($accountEntity);

        $this->registerBeneficiary($accounts, $channel, $accountType);

        $status = $this->checkBeneficiaryRegistrationStatus($accountEntity, $accountType, $channel);

        // add counter for success or failure
        $this->trace->count(
            Metric::BENEFICIARY_REGISTER_API_RESPONSE,
            [
                'status'  => $status,
                'channel' => $channel,
                'mode'    => $this->mode,
            ]);

        if ($status === true)
        {
            $this->removeBeneficiaryRegistrationCacheKey($accountEntity->getId());
        }
        else
        {
            throw new LogicException(
                'Beneficiary registration failed',
                null,
                [
                    'channel'      => $channel,
                    'account_id'   => $accountEntity->getId(),
                    'account_type' => $accountType,
                ]);
        }

        return $status;
    }

    /**
     * Verifies Beneficiary through api based channels, If Verification status is false,
     * It throws logic exception else returns the status.
     *
     * @param Base\Entity $accountEntity
     * @param string $accountType
     * @param string $channel
     *
     * @return bool
     * @throws LogicException
     */
    public function verifyBeneficiaryThroughApi(Base\Entity $accountEntity, string $accountType, string $channel)
    {
        $accounts = (new PublicCollection)->push($accountEntity);

        $this->verifyBeneficiary($accounts, $channel, $accountType);

        $status = $this->checkBeneficiaryVerificationStatus($accountEntity, $accountType, $channel);

        // add counter for success or failure
        $this->trace->count(
            Metric::BENEFICIARY_VERIFY_API_RESPONSE,
            [
                'status'  => $status,
                'channel' => $channel,
                'mode'    => $this->mode,
            ]);

        if ($status === true)
        {
            $this->removeBeneficiaryVerificationCacheKey($accountEntity->getId());
        }
        else
        {
            throw new LogicException(
                'Beneficiary verification failed',
                null,
                [
                    'channel'      => $channel,
                    'account_id'   => $accountEntity->getId(),
                    'account_type' => $accountType,
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

        return $this->repo->bank_account->getBankAccountsBetweenTimestamp($startTime, $endTime);
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
     * @param $accountEntity
     * @param $accountType
     * @param $channel
     * @return bool
     */
    public function checkBeneficiaryRegistrationStatus($accountEntity, $accountType, $channel): bool
    {
        $nodalBeneficiary = $this->getNodalBeneficiaryForAccountType($accountEntity, $accountType, $channel);

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
     * @param $accountEntity
     * @param $accountType
     * @param $channel
     * @return bool
     */
    public function checkBeneficiaryVerificationStatus($accountEntity, $accountType, $channel): bool
    {
        $nodalBeneficiary = $this->getNodalBeneficiaryForAccountType($accountEntity, $accountType, $channel);

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

    /**
     * Get Nodal Beneficiary Status For Bank Account
     *
     * @param $channel
     * @param $accountEntity
     * @param $accountType
     * @return |null
     */
    public function getBeneficiaryStatus($channel, $accountEntity, $accountType = FundAccountType::BANK_ACCOUNT)
    {
        if ($accountType === FundAccountType::BANK_ACCOUNT)
        {
            $nodalBeneficiary = $this->repo
                                     ->nodal_beneficiary
                                     ->fetchActivatedBankAccountBeneficiaryDetailsForChannel(
                                         $accountEntity->getId(),
                                         $channel
                                     );
        }
        else
        {
            $nodalBeneficiary = $this->repo
                                     ->nodal_beneficiary
                                     ->fetchActivatedCardBeneficiaryDetailsForChannel(
                                         $accountEntity->getId(),
                                         $channel
                                     );
        }

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

    /**
     * Remove Bank Account Id Key in cache which denotes that Registration
     * is in progress for that bank account.
     *
     * @param Entity $accountId
     */
    public function removeBeneficiaryRegistrationCacheKey($accountId)
    {
        $cacheKey = ConfigKey::BENEFICIARY_REGISTRATION . $accountId;

        $cacheValue = Cache::pull($cacheKey);

        $this->trace->info(
            TraceCode::BENEFICIARY_REGISTRATION_REDIS_KEY_REMOVED,
            [
                'key' => $cacheKey,
                'value' => $cacheValue,
            ]);
    }

    /**
     * Remove Bank Account Id Key in cache which denotes that Verification
     * is in progress for that bank account.
     *
     * @param Entity $accountId
     */
    public function removeBeneficiaryVerificationCacheKey($accountId)
    {
        $cacheKey = ConfigKey::BENEFICIARY_VERIFICATION . $accountId;

        $cacheValue = Cache::pull($cacheKey);

        $this->trace->info(
            TraceCode::BENEFICIARY_VERIFICATION_REDIS_KEY_REMOVED,
            [
                'key' => $cacheKey,
                'value' => $cacheValue,
            ]);
    }

    /**
     * @param $accountEntity
     * @param $accountType
     * @param $channel
     * @return mixed
     */
    public function getNodalBeneficiaryForAccountType($accountEntity, $accountType, $channel)
    {
        $nodalBeneficiary = null;

        switch ($accountType) {
            case FundAccountType::BANK_ACCOUNT:
                $nodalBeneficiary = $this->repo
                                         ->nodal_beneficiary
                                         ->fetchActivatedBankAccountBeneficiaryDetailsForChannel(
                                             $accountEntity->getId(),
                                             $channel
                                         );
                break;

            case FundAccountType::CARD:
                $nodalBeneficiary = $this->repo
                                         ->nodal_beneficiary
                                         ->fetchActivatedCardBeneficiaryDetailsForChannel(
                                             $accountEntity->getId(),
                                             $channel
                                         );
                break;
        }

        return $nodalBeneficiary;
    }
}
