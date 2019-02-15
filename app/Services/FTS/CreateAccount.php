<?php

namespace RZP\Services\FTS;

use RZP\Models\Vpa;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;

class CreateAccount extends Base
{
    protected $account;

    protected $vpaCore;

    protected $bankAccountCore;

    protected $product;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->vpaCore = new Vpa\Core;

        $this->bankAccountCore = new BankAccount\Core;
    }

    /**
     * Handler method in service for creation
     * of fund account using FTS
     *
     * @param string $id
     * @param string $type
     * @param string $product
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function createFundAccount(string $id, string $type, string $product): array
    {
        $this->product = $product;

        $input = $this->makeRequestUsingType($id, $type);

        $response = $this->createAndSendRequest(parent::FUND_ACCOUNT_BASE_URL, 'POST', $input);

        $ftsFundAccountId = array_key_exists(Constants::FUND_ACCOUNT_ID, $response['body']) ?
            $response['body'][Constants::FUND_ACCOUNT_ID] : null;

        if(empty(trim($ftsFundAccountId)) === false)
        {
            $this->saveFtsAccountId($ftsFundAccountId, $type);
        }

        return $response;
    }

    /**
     * Method to make request to be sent to FTS
     * based on the type of account
     *
     * @param string $id
     * @param string $type
     * @return mixed
     * @throws LogicException
     */
    public function makeRequestUsingType(string $id, string $type)
    {
        $request[Constants::DEFAULT_CHANNEL] = '';

        switch ($type)
        {
            case Constants::BANK_ACCOUNT:
                $this->account = $this->bankAccountCore->getBankAccountEntity($id);

                $request[Constants::BANK_ACCOUNT] = $this->getAccountDetails($this->account);

                break;

            case Constants::VPA:
                $this->account = $this->vpaCore->getVpaEntity($id);

                $request[Constants::VPA] = $this->getVpaDetails($this->account);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $type);
        }

        return $request;
    }

    /**
     * Method to Populate bank account details
     * in an array using entity
     *
     * @param $ba
     * @return array
     */
    public function getAccountDetails(BankAccount\Entity $ba):array
    {
        return [
            Constants::PRODUCT                    => $this->product,
            Constants::IFSC_CODE                  => $ba->getIfscCode(),
            Constants::MERCHANT_ID                => $ba->merchant->getId(),
            Constants::ACCOUNT_TYPE               => $ba->getAccountType(),
            Constants::ACCOUNT_NUMBER             => $ba->getAccountNumber(),
            Constants::BENEFICIARY_NAME           => $ba->getBeneficiaryName(),
            Constants::BENEFICIARY_CITY           => $ba->getBeneficiaryCity(),
            Constants::BENEFICIARY_EMAIL          => $ba->getBeneficiaryEMail(),
            Constants::BENEFICIARY_STATE          => $ba->getBeneficiaryState(),
            Constants::BENEFICIARY_MOBILE         => $ba->getBeneficiaryMobile(),
            Constants::IS_VIRTUAL_ACCOUNT         => $ba->isVirtual(),
            Constants::BENEFICIARY_ADDRESS        => $ba->getBeneficiaryAddress1(),
            Constants::BENEFICIARY_COUNTRY        => $ba->getBeneficiaryCountry(),
            Constants::BENEFICIARY_BANK_NAME      => $ba->getBankName(),
        ];
    }

    /**
     * Method to Populate vpa details
     * in an array using entity
     *
     * @param $vpa
     * @return array
     */
    public function getVpaDetails(Vpa\Entity $vpa):array
    {
        return [
            Constants::HANDLE       => $vpa->getHandle(),
            Constants::PRODUCT      => $this->product,
            Constants::USERNAME     => $vpa->getUsername(),
            Constants::MERCHANT_ID  => $vpa->merchant->getId(),
        ];
    }

    /**
     * Method to persist fts_fund_account_id returned in response
     * to account entities of specific types
     *
     * @param $ftsAccountId
     * @param $type
     * @throws LogicException
     */
    public function saveFtsAccountId($ftsAccountId, $type)
    {
        switch ($type)
        {
            case Constants::BANK_ACCOUNT:
                $this->bankAccountCore->updateBankAccountWithFtsId($this->account, $ftsAccountId);

                break;

            case Constants::VPA:
                $this->vpaCore->updateVpaWithFtsId($this->account, $ftsAccountId);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $type);
        }
    }

}
