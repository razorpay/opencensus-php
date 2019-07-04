<?php

namespace RZP\Models\FundTransfer\Yesbank;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Base;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\NodalBeneficiary\Status;
use RZP\Models\NodalBeneficiary\Entity;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\NodalBeneficiary\Core as NodalCore;
use RZP\Models\FundTransfer\Yesbank\Request\Constants;
use RZP\Models\FundTransfer\Base\Beneficiary\ApiProcessor;
use RZP\Models\FundTransfer\Yesbank\Request\Beneficiary as BeneficiaryRequest;

class Beneficiary extends ApiProcessor
{
    protected $channel = Channel::YESBANK;

    /**
     * Makes beneficiary addition request for the bank account ids provided
     * Slack notification will be sent as a summary
     *
     * @param PublicCollection $bankAccounts
     */
    public function process(PublicCollection $bankAccounts)
    {
        $this->count = $bankAccounts->count();

        // TODO: Figure out how to do beneficiary registration on two different nodal accounts of the same channel!
        $request = new BeneficiaryRequest;

        foreach ($bankAccounts as $bankAccount)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BENEFICIARY_REGISTER_BANK_ACCOUNT,
                    [
                        'bank_account_id'   => $bankAccount->getId(),
                        'channel'           => $this->channel
                    ]);

                $input = [
                    Entity::CHANNEL             => Channel::YESBANK,
                    Entity::MERCHANT_ID         => $bankAccount->merchant->getId(),
                    Entity::BANK_ACCOUNT_ID     => $bankAccount->getId(),
                    Entity::BENEFICIARY_CODE    => $bankAccount->getBeneficiaryCode(),
                    Entity::REGISTRATION_STATUS => Status::CREATED
                ];

                $nodalBeneficiary = $this->repo->nodal_beneficiary
                                         ->fetchNonRegisteredBeneficiary(
                                             $bankAccount->getId(),
                                             Channel::YESBANK
                                         );

                $status = $this->checkBeneficiaryStatusForRegistration($input, $nodalBeneficiary);

                if ($status === false)
                {
                    continue;
                }

                $beneRegResponse = $request->init()
                                           ->setEntity($bankAccount)
                                           ->makeRequest();

                $beneStatus = $this->getBeneficiaryStatus($beneRegResponse);

                if ($beneStatus === Status::FAILED)
                {
                    $this->summary[] = $bankAccount->getId();
                }

                $this->updateBeneficiaryStatus($bankAccount, $beneStatus);
            }
            catch (\Throwable $e)
            {
                $this->summary[] = $bankAccount->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::BENEFICIARY_REGISTRATION_FAILED,
                    [
                        'bank_account_id' => $bankAccount->getId(),
                        'error'           => $e->getMessage()
                    ]);

                $this->updateBeneficiaryStatus($bankAccount, Status::FAILED);
            }
        }
    }

    /**
     * Updates beneficiary status
     *
     * @param BankAccount $bankAccount
     * @param string      $beneficiaryStatus
     */
    protected function updateBeneficiaryStatus(BankAccount $bankAccount, string $beneficiaryStatus)
    {
        $beneficiaryUpdateInfo = [
            Entity::CHANNEL             => Channel::YESBANK,
            Entity::BANK_ACCOUNT_ID     => $bankAccount->getId(),
            Entity::REGISTRATION_STATUS => $beneficiaryStatus
        ];

        (new NodalCore)->update($beneficiaryUpdateInfo);
    }

    /**
     * Extract beneficiary status from response
     *
     * @param array $response
     * @return string
     */
    protected function getBeneficiaryStatus(array $response): string
    {
        if ((array_key_exists(Constants::REQUEST_STATUS, $response) === true) and
            ($response[Constants::REQUEST_STATUS] === Constants::SUCCESS))
        {
            return Status::REGISTERED;
        }

        if ((array_key_exists(Constants::REQUEST_STATUS, $response) === true) and
            ($response[Constants::REQUEST_STATUS] === Constants::FAILURE))
        {
            $errorMessage = $response[Constants::ERROR];

            if ($errorMessage === BeneficiaryRequest::RECORD_EXIST)
            {
                return Status::REGISTERED;
            }
        }

        return Status::FAILED;
    }

    protected function checkBeneficiaryStatusForRegistration(array $input, $nodalBeneficiary): bool
    {
        if ($nodalBeneficiary === null)
        {
            (new NodalCore)->create($input);

            return true;
        }

        $registrationStatus = $nodalBeneficiary->getRegistrationStatus();

        if ($registrationStatus !== Status::REGISTERED)
        {
            return true;
        }

        return false;
    }
}
