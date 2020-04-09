<?php


namespace RZP\Models\BankTransferHistory;

use RZP\Models\Base;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\BankTransfer\Entity as BankTransferEntity;

class Service extends Base\Service
{
    protected $core;
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->validator = new Validator();
    }

    public function backupPayerBankAccount(BankTransferEntity $bankTransfer, array & $input)
    {
        $data = [
            Entity::BANK_TRANSFER_ID         => $bankTransfer->getId(),
            Entity::PAYER_NAME               => ($this->existsAndNotNull($input, BankAccountEntity::BENEFICIARY_NAME) === true) ?   $bankTransfer->getPayerName()             : null,
            Entity::PAYER_ACCOUNT            => ($this->existsAndNotNull($input, BankAccountEntity::ACCOUNT_NUMBER) === true) ?     $bankTransfer->getPayerAccount()          : null,
            Entity::PAYER_IFSC               => ($this->existsAndNotNull($input, BankAccountEntity::IFSC_CODE) === true) ?          $bankTransfer->getPayerIfsc()             : null,
            Entity::PAYER_BANK_ACCOUNT_ID    => ($this->existsAndNotNull($input, 'bank_account_id') === true) ?                     $bankTransfer->getPayerBankAccountId()    : null,
        ];

        if ($this->auth->isAdminAuth() === true)
        {
            $data[Entity::CREATED_BY] = $this->app['basicauth']->getAdmin()->getEmail();
        }
        else
        {
            $data[Entity::CREATED_BY] = (isset($input[Entity::CREATED_BY]) === true) ? $input[Entity::CREATED_BY] : null;
        }

        $this->validator->validateInput('create', $data);

        $bankTransferHistory = $this->core->backupPayerBankAccount($data);

        unset($input[Entity::CREATED_BY]);

        return $bankTransferHistory->toArrayPublic();
    }

    protected function existsAndNotNull(array & $input, string $key) : bool
    {
        if (isset($input[$key]) === true)
        {
            if (empty($input[$key]) === false)
            {
                return true;
            }
            else
            {
                unset($input[$key]);
            }
        }

        return false;
    }
}
