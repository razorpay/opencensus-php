<?php


namespace RZP\Models\VirtualAccountTpv;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\VirtualAccount;

class Core extends Base\Core
{
    public function create(VirtualAccount\Entity $virtualAccount, $allowedPayer) : Entity
    {
        $virtualAccountTpv = new Entity();

        $virtualAccountTpv->virtualAccount()->associate($virtualAccount);

        $virtualAccountTpv->entity()->associate($allowedPayer);

        $virtualAccountTpv->setIsActive(true);

        $this->repo->saveOrFail($virtualAccountTpv);

        return $virtualAccountTpv;
    }

    public function buildAllowedPayers(VirtualAccount\Entity $virtualAccount, array $input)
    {
        if (isset($input[VirtualAccount\Entity::ALLOWED_PAYERS]) === false)
        {
            return;
        }

        $this->validateReceiversForTpv($virtualAccount);

        $allowedPayers = $input[VirtualAccount\Entity::ALLOWED_PAYERS];

        (new Validator())->validateAllowedPayers($allowedPayers);

        $allowedPayers = array_unique($allowedPayers, SORT_REGULAR);

        foreach ($allowedPayers as $allowedPayer)
        {
            $this->addAllowedPayer($virtualAccount, $allowedPayer);
        }

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_ALLOWED_PAYERS_CREATED,
            [
                'virtual_account_id'    => $virtualAccount->getPublicId(),
            ]
        );
    }

    public function validateReceiversForTpv(VirtualAccount\Entity $virtualAccount)
    {
        $vpa = $virtualAccount->vpa;

        $qrCode = $virtualAccount->qrCode;

        if ((($vpa !== null) and ($vpa->getHandle() === 'icici')) or
            ($qrCode !== null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_RECEIVER_TYPES_NOT_ALLOWED
            );
        }
    }

    protected function buildAllowedPayerBankAccount(array $input, Merchant\Entity $merchant)
    {
        $input[BankAccount\Entity::BENEFICIARY_NAME] = $merchant->getFilteredDba();

        $bankAccount = (new BankAccount\Entity())->build($input, 'addTpvBankAccountForVa');

        $bankAccount->merchant()->associate($merchant);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    public function deactivate(Entity $virtualAccountTpv)
    {
        $deactivated_at = Carbon::now(Timezone::IST)->getTimestamp();

        $virtualAccountTpv->deactivate($deactivated_at);

        $this->repo->saveOrFail($virtualAccountTpv);
    }

    public function addAllowedPayer($virtualAccount, $allowedPayer)
    {
        $type = $allowedPayer[Entity::TYPE];

        $payerInput = $allowedPayer[$type];

        $func = 'buildAllowedPayer' . studly_case($type);

        $allowedPayerEntity = $this->$func($payerInput, $virtualAccount->merchant);

        $virtualAccountTpv = $this->create($virtualAccount, $allowedPayerEntity);

        $allowedPayerEntity->source()->associate($virtualAccountTpv);

        $this->repo->saveOrFail($allowedPayerEntity);
    }

    public function addAllowedPayerToExistingVa($virtualAccount, $allowedPayer)
    {
        (new Validator())->validateAllowedPayer($allowedPayer);

        $this->validateReceiversForTpv($virtualAccount);

        $this->validateAllowedPayerExists($virtualAccount, $allowedPayer);

        $this->repo->transaction(function() use ($virtualAccount, $allowedPayer)
        {
            $this->addAllowedPayer($virtualAccount, $allowedPayer);
        });

        return $virtualAccount;
    }

    private function validateAllowedPayerExists($virtualAccount, $allowedPayer)
    {
        $existingAllowedPayers = $virtualAccount->virtualAccountTpv()->get();

        foreach ($existingAllowedPayers as $existingAllowedPayer)
        {
            $isDuplicate = $existingAllowedPayer->isDuplicate($allowedPayer);

            if ($isDuplicate === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_ALREADY_EXISTS
                );
            }
        }
    }

    public function deleteAllowedPayer(Base\PublicEntity $virtualAccount, $tpvEntityId)
    {
        $entityId = BankAccount\Entity::verifyIdAndStripSign($tpvEntityId);

        $virtualAccountTpv = $this->repo
                                  ->virtual_account_tpv
                                  ->fetchByVirtualAccountIdAndEntityId($virtualAccount->getId(), $entityId);

        if ($virtualAccountTpv === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_ALLOWED_PAYER_ID,
                'allowed_payer_id'
            );
        }

        $this->repo->transaction(function() use ($virtualAccountTpv)
        {
            $this->repo->deleteOrFail($virtualAccountTpv->entity);

            $this->repo->deleteOrFail($virtualAccountTpv);
        });
    }
}
