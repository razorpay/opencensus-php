<?php

namespace RZP\Models\NodalBeneficiary;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\BankAccount;

class Repository extends Base\Repository
{
    protected $entity = 'nodal_beneficiary';

    protected $appFetchParamRules = [
        Entity::CHANNEL             => 'filled|string|max:8',
        Entity::MERCHANT_ID         => 'filled|string|size:14',
        Entity::CARD_ID             => 'sometimes|string|size:14',
        Entity::BANK_ACCOUNT_ID     => 'sometimes|string|size:14',
        Entity::REGISTRATION_STATUS => 'filled|string|max:40',
        Entity::BENEFICIARY_CODE    => 'sometimes|filled|nullable|string|max:30',
    ];

    /**
     * @param string $bankAccountId
     * @param string $channel
     * @return Entity
     */
    public function fetchBankAccountBeneficiaryDetailsForChannel(string $bankAccountId, string $channel): Entity
    {
        return $this->newQuery()
                    ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                    ->where(Entity::CHANNEL, $channel)
                    ->withTrashed()
                    ->firstorFail();
    }

    public function fetchCardBeneficiaryDetailsForChannel(string $cardId, string $channel): Entity
    {
        return $this->newQuery()
                    ->where(Entity::CARD_ID, $cardId)
                    ->where(Entity::CHANNEL, $channel)
                    ->withTrashed()
                    ->firstorFail();
    }

    public function fetchNonRegisteredBankAccountBeneficiary(string $bankAccountId, string $channel)
    {
        $result =  $this->newQuery()
                        ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                        ->where(Entity::CHANNEL, $channel)
                        ->withTrashed()
                        ->first();

        return $result;
    }

    public function fetchNonRegisteredCardBeneficiary(string $cardId, string $channel)
    {
        $result =  $this->newQuery()
                        ->where(Entity::CARD_ID, $cardId)
                        ->where(Entity::CHANNEL, $channel)
                        ->withTrashed()
                        ->first();

        return $result;
    }

    public function fetchNonRegisteredBankAccount(string $channel): array
    {
        return $this->newQuery()
                    ->select(Entity::BANK_ACCOUNT_ID)
                    ->where(Entity::CHANNEL, $channel)
                    ->where(Entity::REGISTRATION_STATUS, '!=', Status::REGISTERED)
                    ->withTrashed()
                    ->pluck(Entity::BANK_ACCOUNT_ID)->toArray();
    }

    /**
     * @param string $bankAccountId
     * @param string $channel
     * @return mixed
     */
    public function fetchActivatedBankAccountBeneficiaryDetailsForChannel(string $bankAccountId, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                    ->where(Entity::CHANNEL, $channel)
                    ->first();
    }

    /**
     * @param string $cardId
     * @param string $channel
     * @return mixed
     */
    public function fetchActivatedCardBeneficiaryDetailsForChannel(string $cardId, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::CARD_ID, $cardId)
                    ->where(Entity::CHANNEL, $channel)
                    ->first();
    }

    /**
     * @param string $beneficiaryName
     * @param string $beneficiaryIfsc
     * @param string $beneficiaryAccountNumber
     * @param string $channel
     * @param string $type
     * @return mixed
     */
    public function fetchRegisteredBeneficiaryCodeForBeneDetails(string $beneficiaryName,
                                                                 string $beneficiaryIfsc,
                                                                 string $beneficiaryAccountNumber,
                                                                 string $channel,
                                                                 string $type)
    {

        $bankAccountRef       = $this->repo->bank_account;
        $nodalBeneficiaryRef  = $this->repo->nodal_beneficiary;

        $bankAccountIdColumn                      =   $bankAccountRef->dbColumn(BankAccount\Entity::ID);
        $bankAccountBeneficiaryNameColumn         =  $bankAccountRef->dbColumn(BankAccount\Entity::BENEFICIARY_NAME);
        $bankAccountBeneficiaryIfscColumn         = $bankAccountRef->dbColumn(BankAccount\Entity::IFSC_CODE);
        $bankAccountBeneficiaryAccountNoColumn    =  $bankAccountRef->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);
        $bankAccountBeneficiaryTypeColumn         = $bankAccountRef->dbColumn(BankAccount\Entity::TYPE);
        $bankAccountBeneficiaryCreatedAtColumn    =  $bankAccountRef->dbColumn(BankAccount\Entity::CREATED_AT);

        $nodalBeneficiaryBankAccountIdColumn      = $nodalBeneficiaryRef->dbColumn(Entity::BANK_ACCOUNT_ID);
        $nodalBeneficiaryChannelColumn            = $nodalBeneficiaryRef->dbColumn(Entity::CHANNEL);
        $nodalBeneficiaryRegistrationStatusColumn = $nodalBeneficiaryRef->dbColumn(Entity::REGISTRATION_STATUS);

        return  $this->newQueryWithConnection($this->getSlaveConnection())
                     ->join(Table::BANK_ACCOUNT, $nodalBeneficiaryBankAccountIdColumn, '=', $bankAccountIdColumn)
                     ->where($bankAccountBeneficiaryNameColumn,'=',$beneficiaryName)
                     ->where($bankAccountBeneficiaryIfscColumn,'=',$beneficiaryIfsc)
                     ->where($bankAccountBeneficiaryAccountNoColumn,'=',$beneficiaryAccountNumber)
                     ->where($bankAccountBeneficiaryTypeColumn,'=',$type)
                     ->where($nodalBeneficiaryChannelColumn,'=', $channel)
                     ->where($nodalBeneficiaryRegistrationStatusColumn ,'=', status::VERIFIED)
                     ->orderBy($bankAccountBeneficiaryCreatedAtColumn, 'desc')
                     ->limit(1)
                     ->pluck($nodalBeneficiaryBankAccountIdColumn)->toArray();
    }

}
