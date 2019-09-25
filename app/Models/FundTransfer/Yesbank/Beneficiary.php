<?php

namespace RZP\Models\FundTransfer\Yesbank;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\FundAccount\Type;
use RZP\Models\FundTransfer\Base;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\NodalBeneficiary\Status;
use RZP\Models\NodalBeneficiary\Entity;
use RZP\Models\Base\Entity as BaseEntity;
use RZP\Models\NodalBeneficiary\Core as NodalCore;
use RZP\Models\FundTransfer\Yesbank\Request\Constants;
use RZP\Models\FundTransfer\Base\Beneficiary\ApiProcessor;
use RZP\Models\FundTransfer\Yesbank\Request\Beneficiary as BeneficiaryRequest;
use RZP\Models\FundTransfer\Yesbank\Request\VerifyBeneficiary as VerifyBeneficiaryRequest;

class Beneficiary extends ApiProcessor
{
    protected $channel = Channel::YESBANK;

    /**
     * Makes beneficiary addition request for the bank account ids provided
     * Slack notification will be sent as a summary
     *
     * @param PublicCollection $accounts
     * @param $action
     */
    public function process(PublicCollection $accounts, $action)
    {
        if ($action === Constants::VERIFY_BENE_FLAG)
        {
            $this->processVerification($accounts);
        }
        else
        {
            $this->processRegistration($accounts);
        }
    }

    protected function processVerification(PublicCollection $accounts)
    {
        $this->count = $accounts->count();

        // Creating beneficiary Verification Request to YesBank
        $request = new VerifyBeneficiaryRequest;

        foreach ($accounts as $account)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BENEFICIARY_VERIFY_ACCOUNT,
                    [
                        'channel'      => $this->channel,
                        'account_id'   => $account->getId(),
                        'account_type' => $this->accountType,
                    ]);

                $nodalBeneficiary = $this->getNodalBeneficiaryForAccountType($account);

                $status = $this->checkBeneficiaryStatusForVerification($nodalBeneficiary);

                if ($status === false)
                {
                    continue;
                }

                $beneVerifyResponse = $request->init()
                                              ->setEntity($account)
                                              ->setEntityType($this->accountType)
                                              ->makeRequest();

                $beneStatus = $this->getBeneficiaryStatusAfterVerification($beneVerifyResponse);

                if ($beneStatus === Status::FAILED)
                {
                    $this->summary[] = $account->getId();
                }

                if ($beneStatus !== Status::REGISTERED)
                {
                    $this->updateBeneficiaryStatus($account, $beneStatus);
                }
            }
            catch (\Throwable $e)
            {
                $this->summary[] = $account->getId();

                $this->summary[] = $this->accountType;

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::BENEFICIARY_VERIFY_FAILED,
                    [
                        'error'         => $e->getMessage(),
                        'account_id'    => $account->getId(),
                        'account_type'  => $this->accountType,
                    ]);

                $this->updateBeneficiaryStatus($account, Status::FAILED);
            }
        }
    }

    /**
     * Updates beneficiary status
     *
     * @param BaseEntity $account
     * @param string      $beneficiaryStatus
     */
    protected function updateBeneficiaryStatus(BaseEntity $account, string $beneficiaryStatus)
    {
        $beneficiaryUpdateInfo = [
            Entity::CHANNEL             => Channel::YESBANK,
            Entity::REGISTRATION_STATUS => $beneficiaryStatus
        ];

        switch ($this->accountType)
        {
            case Type::BANK_ACCOUNT:
                $beneficiaryUpdateInfo[Entity::BANK_ACCOUNT_ID] = $account->getId();

                (new NodalCore)->updateNodalBeneficiaryWithBankAccount($beneficiaryUpdateInfo);

                break;

            case Type::CARD:
                $beneficiaryUpdateInfo[Entity::CARD_ID] = $account->getId();

                (new NodalCore)->updateNodalBeneficiaryWithCard($beneficiaryUpdateInfo);

                break;
        }


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

    /**
     * Extract beneficiary status from response
     *
     * @param array $response
     * @return string
     */
    protected function getBeneficiaryStatusAfterVerification(array $response): string
    {
        if ((array_key_exists(Constants::REQUEST_STATUS, $response) === true) and
            ($response[Constants::REQUEST_STATUS] === Constants::SUCCESS))
        {
            return Status::VERIFIED;
        }

        if ((array_key_exists(Constants::REQUEST_STATUS, $response) === true) and
            ($response[Constants::REQUEST_STATUS] === Constants::FAILURE))
        {
            $errorMessage = $response[Constants::ERROR];

            if ($errorMessage === BeneficiaryRequest::RECORD_EXIST)
            {
                return Status::VERIFIED;
            }

            if ($errorMessage === BeneficiaryRequest::RECORD_DOES_NOT_EXIST)
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
            if ($this->accountType === Type::CARD)
            {
                (new NodalCore)->createWithCard($input);
            }
            else
            {
                (new NodalCore)->createWithBankAccount($input);
            }

            return true;
        }

        $registrationStatus = $nodalBeneficiary->getRegistrationStatus();

        if ($registrationStatus !== Status::REGISTERED)
        {
            return true;
        }

        return false;
    }

    protected function checkBeneficiaryStatusForVerification($nodalBeneficiary): bool
    {
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
     * @param PublicCollection $accounts
     */
    protected function processRegistration(PublicCollection $accounts)
    {
        $this->count = $accounts->count();

        // TODO: Figure out how to do beneficiary registration on two different nodal accounts of the same channel!
        $request = new BeneficiaryRequest;

        foreach ($accounts as $account)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BENEFICIARY_REGISTER_ACCOUNT,
                    [
                        'channel'      => $this->channel,
                        'account_id'   => $account->getId(),
                        'account_type' => $this->accountType,
                    ]);

                $input = [
                    Entity::CHANNEL => Channel::YESBANK,
                    Entity::MERCHANT_ID => $account->merchant->getId(),
                    Entity::REGISTRATION_STATUS => Status::CREATED
                ];

                switch ($this->accountType)
                {
                    case Type::BANK_ACCOUNT:
                        $input[Entity::BANK_ACCOUNT_ID] = $account->getId();

                        $input[Entity::BENEFICIARY_CODE] = $account->getBeneficiaryCode();

                        break;

                    case Type::CARD:
                        $input[Entity::CARD_ID] = $account->getId();

                        break;

                    default:
                        $this->trace->info(
                            TraceCode::ACCOUNT_TYPE_NOT_SUPPORTED_FOR_BENE_REG,
                            [
                                'account_id'   => $account->getId(),
                                'account_type' => $this->accountType,
                            ]);

                        continue;
                }

                $nodalBeneficiary = $this->getNodalBeneficiaryForAccountType($account);

                $status = $this->checkBeneficiaryStatusForRegistration($input, $nodalBeneficiary);

                if ($status === false)
                {
                    continue;
                }

                $beneRegResponse = $request->init()
                                           ->setEntity($account)
                                           ->setEntityType($this->accountType)
                                           ->makeRequest();

                $beneStatus = $this->getBeneficiaryStatus($beneRegResponse);

                if ($beneStatus === Status::FAILED)
                {
                    $this->summary[] = $account->getId();
                }

                $this->updateBeneficiaryStatus($account, $beneStatus);
            }
            catch (\Throwable $e)
            {
                $this->summary[] = $account->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::BENEFICIARY_REGISTRATION_FAILED,
                    [
                        'error'         => $e->getMessage(),
                        'account_id'    => $account->getId(),
                        'account_type'  => $this->accountType,
                    ]);

                $this->updateBeneficiaryStatus($account, Status::FAILED);
            }
        }
    }

    /**
     * @param $accountEntity
     * @param $accountType
     * @param $channel
     * @return mixed
     */
    public function getNodalBeneficiaryForAccountType($accountEntity)
    {
        $nodalBeneficiary = null;

        switch ($this->accountType) {
            case Type::BANK_ACCOUNT:
                $nodalBeneficiary = $this->repo->nodal_beneficiary
                                               ->fetchNonRegisteredBankAccountBeneficiary(
                                                    $accountEntity->getId(),
                                                    Channel::YESBANK
                                                );

                break;

            case Type::CARD:
                $nodalBeneficiary = $this->repo->nodal_beneficiary
                                               ->fetchNonRegisteredCardBeneficiary(
                                                    $accountEntity->getId(),
                                                    Channel::YESBANK
                                               );

                break;

        }

        return $nodalBeneficiary;
    }
}
