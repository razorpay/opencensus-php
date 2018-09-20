<?php

namespace RZP\Models\BankAccount;

use App;
use Mail;
use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Exception\InvalidArgumentException;

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

        $this->app['slack']->queue(
            $message,
            [
                'channel' => $channel,
            ],
            [
                'channel' => Config::get('slack.channels.settlements')
            ]);

        return $result;
    }

    protected function registerBeneficiary(
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
    public function registerBeneficiaryThroughApi(array $input, string $channel): array
    {
        $bankAccounts = new Base\PublicCollection;

        $this->trace->info(
            TraceCode::BENEFICIARY_REGISTER_API_INIT,
            [
                'input'   => $input,
                'channel' => $channel
            ]
        );

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
                   "last report is ". $beneficiaryCount; ;

        $this->app['slack']->queue(
            $message,
            [
                'channel' => $channel
            ],
            [
                'channel' => Config::get('slack.channels.settlements')
            ]);

        return $result;
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
            ]
        );

        return $this->repo->bank_account->getMerchantBankAccountsBetweenTimestamp($startTime, $endTime);
    }

    /**
     * @return Base\PublicCollection
     */
    protected function fetchNonRegisteredBankAccount($channel): Base\PublicCollection
    {
        $bankAccount = $this->repo->nodal_beneficiary->fetchNonRegisteredBankAccount($channel);

        if (empty($bankAccount) === true)
        {
            return new Base\PublicCollection();
        }

        return $this->repo->bank_account->findMany($bankAccount);
    }
}
