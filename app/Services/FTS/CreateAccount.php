<?php

namespace RZP\Services\FTS;

use RZP\Exception\LogicException;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\BankAccount\Core as BankAccountCore;

class CreateAccount extends Base
{
    protected $account;

    protected $bankAccountCore;

    protected $vpaCore;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->bankAccountCore = new BankAccountCore;

        $this->vpaCore = new VPACore;
    }

    /**
     * Handler method in service for creation
     * of fund account using FTS
     *
     * @param string $id
     * @param string $type
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function createFundAccount(string $id, string $type): array
    {
        $input = $this->makeRequestUsingType($id, $type);

        $response = $this->createAndSendRequest(parent::FundAccountBaseURL, 'POST', $input);

        $ftsAccountId = array_key_exists('fa_id', $response['body']) ? $response['body']['fa_id'] : null;

        if(empty(trim($ftsAccountId)) === false)
        {
            $this->saveFTSAccountId($ftsAccountId, $this->account);
        }

        return $response;
    }

    /**
     * Method to make request to be sent to FTS
     * based on the type of account
     *
     * @param string $id
     * @param string $type
     */
    public function makeRequestUsingType(string $id, string $type)
    {
        $request[Constants::DEFAULT_CHANNEL] = '';

        switch ($type)
        {
            case Constants::BANK_ACCOUNT:
                $account = $this->bankAccountCore->getBankAccountEntity($id);

                $request[Constants::BANK_ACCOUNT] = $this->getAccountDetails($account);

                break;

            case Constants::VPA:
                $account = $this->vpaCore->getVPAEntity($id);

                $request[Constants::VPA] = $this->getVPADetails($account);

                break;

            default:
                throw new LogicException('Type is not supported ' . $this->type);
        }
    }

    /**
     * Method to Populate bank account details
     * in an array using entity
     *
     * @param $ba
     * @return array
     */
    public function getAccountDetails($ba):array
    {
        //TODO:: Logic to Add Product
        return [
            Constants::ID                         => $ba->getId(),
            Constants::TYPE                       => $ba->getType(),
            Constants::MERCHANT_ID                => $ba->merchant->getId(),
            Constants::BENEFICIARY_PIN            => $ba->getBeneficiaryPin(),
            Constants::BENEFICIARY_NAME           => $ba->getBeneficiaryName(),
            Constants::BENEFICIARY_CODE           => $ba->getBeneficiaryCode(),
            Constants::BENEFICIARY_CITY           => $ba->getBeneficiaryCity(),
            Constants::BENEFICIARY_STATE          => $ba->getBeneficiaryState(),
            Constants::BENEFICIARY_MOBILE         => $ba->getBeneficiaryMobile(),
            Constants::BENEFICIARY_ADDRESS        => $ba->getBeneficiaryAddress1(),
            Constants::BENEFICIARY_COUNTRY        => $ba->getBeneficiaryCountry(),
            Constants::BENEFICIARY_EMAIL_ID       => $ba->getBeneficiaryEMail(),
            Constants::BENEFICIARY_IFSC_CODE      => $ba->getIfscCode(),
            Constants::BENEFICIARY_BANK_NAME      => $ba->getBankName(),
            Constants::BENEFICIARY_ACCOUNT_TYPE   => $ba->getAccountType(),
            Constants::BENEFICIARY_ACCOUNT_NUMBER => $ba->getAccountNumber(),
        ];
    }

    /**
     * Method to Populate vpa details
     * in an array using entity
     *
     * @param $vpa
     * @return array
     */
    public function getVPADetails($vpa):array
    {
        //TODO:: Logic to Add Product
        return [
            Constants::HANDLE       => $vpa->getHandle(),
            Constants::USERNAME     => $vpa->getUsername(),
            Constants::MERCHANT_ID  => $vpa->merchant->getId(),
        ];
    }

    /**
     * Method to persist fts_account_id returned in response
     * to account entities of specific types
     *
     * @param $ftsAccountId
     * @param $account
     */
    public function saveFTSAccountId($ftsAccountId, $account)
    {
        $account->setFTSAccountId($ftsAccountId);

        switch ($this->type)
        {
            case Constants::BANK_ACCOUNT:
                $this->bankAccountCore->updateBankAccountEntity($account);

                break;

            case Constants::VPA:
                $this->vpaCore->updateVPAEntity($account);

                break;

            default:
                throw new LogicException('Type is not supported ' . $this->type);
        }
    }

}
