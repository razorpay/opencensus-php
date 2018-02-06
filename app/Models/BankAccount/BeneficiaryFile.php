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
use RZP\Models\Settlement\Holidays;

class BeneficiaryFile extends Base\Core
{
    public function generate(string $channel): array
    {
        $bankAccounts = (new BankAccount\Repository)->getAllActivatedMerchantAccountsOrderedByCreatedAt();

        $result = $this->generateBeneficiaryFile($bankAccounts, $channel);

        return $result;
    }

    public function generateBetweenTimestamps(array $input, string $channel): array
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

        $message = "Merchant Beneficiary file generated. Beneficiary added since".
            " last report is ". $newBeneficiaryCount;

        $this->app['slack']->queue($message, [], ['channel' => Config::get('slack.channels.settlements')]);

        $this->trace->info(
            TraceCode::MERCHANT_BENEFICIARY_FILE_GENERATE,
            ['new_beneficiaries_added' => $newBeneficiaryCount]);

        $result = $this->generateBeneficiaryFile($bankAccounts, $channel, $input);

        return $result;
    }

    protected function generateBeneficiaryFile(
        Base\PublicCollection $bankAccounts,
        string $channel,
        array $input = []): array
    {
        $beneClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\Beneficiary';

        $response = (new $beneClass)->register($bankAccounts, $input);

        return $response;
    }
}
