<?php

namespace RZP\Models\BankAccount;

use App;
use Mail;
use Config;
use Carbon\Carbon;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Models\NodalBeneficiary\Status;
use RZP\Jobs\BeneficiaryRegistration;
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
        //
        // We don't have to register beneficiary for the bank account created in test mode.
        //
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        //
        // We enqueue bank account with all the available channels which provide API based bene registration.
        //
        $channels = Channel::getChannelsWithOnlineBeneficiaryRegistration();

        foreach ($channels as $channel)
        {
            $this->dispatchBankAccount($bankAccount, $channel);
        }
    }

    /**
     * Push the bank account id to the queue along with the channel on which bene registration
     * has to be performed. Also suppresses error which might happen because of queue
     *
     * @param Entity $bankAccount
     * @param string $channel
     */
    protected function dispatchBankAccount(Entity $bankAccount, string $channel)
    {
        try
        {
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

    public function registerBetweenTimestamps(array $input, string $channel): array
    {
        (new Validator)->validateInput('beneficiary_register', $input);

        if (isset($input[Entity::ON]))
        {
            $today = Carbon::createFromTimestamp($input['on'], Timezone::IST);
        }
        else
        {
            $today = Carbon::today(Timezone::IST);
        }

        if (Holidays::isWorkingDay($today) === false)
        {
            return ['message' => 'Today is a holiday! Happy holidays :)'];
        }

        $from = Holidays::getPreviousWorkingDay($today);

        $bankAccounts = $this->repo->bank_account->getMerchantBankAccountsBetweenTimestamp(
            $from->getTimestamp(),
            $today->getTimestamp());

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
        $message = "Merchant Beneficiary file generated. Beneficiary added since".
            " last report is ". $newBeneficiaryCount;

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

        $result = $this->registerBeneficiary($bankAccounts, $channel, $input);

        $beneficiaryCount = $bankAccounts->count();

        $message = "Merchant Beneficiary api executed. Beneficiary added since ".
                   "last report is ". $beneficiaryCount;

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
                "Beneficiary registration failed",
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
     * @param string $channel
     * @param Entity $bankAccount
     * @return bool
     */
    public function registerBeneficiaryOnChannelAndGetStatus(string $channel, Entity $bankAccount): bool
    {
        $data = [
            'bank_account_id'  => $bankAccount->getId(),
            'channel'          => $channel,
        ];

        $beneClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\Beneficiary';

        $this->trace->info(TraceCode::FTA_MERCHANT_BENE_REG_INIT, $data);

        $bankAccounts = (new PublicCollection)->push($bankAccount);

        $beneResponse = (new $beneClass)->registerBeneficiary($bankAccounts);

        $this->trace->info(TraceCode::FTA_MERCHANT_BENE_REG_COMPLETE, $data + $beneResponse);

        return $this->checkBeneficiaryRegistrationStatus($bankAccount, $channel);
    }

    /**
     * @param $bankAccount
     * @param $channel
     * @return bool
     */
    protected function checkBeneficiaryRegistrationStatus($bankAccount, $channel): bool
    {
        $nodalBeneficiary = $this->repo
                                 ->nodal_beneficiary
                                 ->fetchActivatedBeneficiaryDetailsForChannel(
                                     $bankAccount->getId(),
                                     $channel
                                 );

        $registrationStatus = $nodalBeneficiary->getRegistrationStatus();

        if ($registrationStatus === Status::REGISTERED)
        {
            return true;
        }

        return false;
    }
}
