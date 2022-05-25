<?php


namespace RZP\Models\CreditTransfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\CreditTransfer\Helper as Helper;

class Core extends Base\Core
{
    public function getHelper(Base\Entity $source): Helper\Base
    {
        $type = $source->getEntityName();

        $helper = __NAMESPACE__ ;

        $helper .= '\\Helper\\' .studly_case($type);

        return new $helper($source);
    }

    public function create(Base\Entity $source): Entity
    {
        $creditTransfer = $this->repo->credit_transfer->findCreditTransferBySourceId($source->getId());

        if (is_null($creditTransfer) === false)
        {
            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_ALREADY_EXISTS,
                [
                    'source_id'              => $source->getId(),
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]
            );

            return $creditTransfer;
        }

        $this->trace->info(
            TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_REQUEST,
            [
                'source_id'              => $source->getId(),
            ]
        );

        $sourceEntityHelper = $this->getHelper($source);

        $creditAccountType = $sourceEntityHelper->getCreditAccountTypeFromSourceEntity();

        AccountType::validate($creditAccountType);

        $destinationVirtualAccount = $sourceEntityHelper->getDestinationVirtualAccountFromSourceEntity();

        $merchant = $destinationVirtualAccount->merchant;

        $balance = $destinationVirtualAccount->balance;

        $creditTransferInput = $sourceEntityHelper->getCreditTransferInputFromSourceEntity();

        switch ($creditAccountType)
        {
            case AccountType::BANK_ACCOUNT:
                $creditTransferInput = array_merge($creditTransferInput, [
                                            Entity::PAYEE_ACCOUNT_ID   => $destinationVirtualAccount->getBankAccountId(),
                                            Entity::PAYEE_ACCOUNT_TYPE => FundAccount\Type::BANK_ACCOUNT
                                        ]);
                break ;
        }

        $creditTransfer = (new Entity);

        $creditTransfer->balance()->associate($balance);

        $creditTransfer->merchant()->associate($merchant);

        $creditTransfer->entity()->associate($source);

        $creditTransfer->setStatus(Status::CREATED);

        $creditTransfer = $creditTransfer->build($creditTransferInput);

        $this->repo->saveOrFail($creditTransfer);

        $this->trace->info(TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_SUCCESS,
            [
                'credit_transfer' => $creditTransfer->toArrayPublic()
            ]
        );

        return $creditTransfer;
    }

    public function process(Entity $creditTransfer): Entity
    {
        $creditTransfer->reload();

        if ($creditTransfer->isStatusProcessed() === true)
        {
            return $creditTransfer;
        }

        $creditTransfer = $this->repo->transaction(function () use ($creditTransfer)
        {
            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_PROCESS_ATTEMPT,
                $creditTransfer->toArrayPublic());

            $txn = (new Transaction\Core)->createFromCreditTransfer($creditTransfer);

            $this->repo->saveOrFail($txn);

            $creditTransfer->setStatus(Status::PROCESSED);

            $utr = UniqueIdEntity::generateUniqueId();

            $creditTransfer->setUtr($utr);

            $this->repo->saveOrFail($creditTransfer);

            (new Transaction\Core)->dispatchEventForTransactionCreated($txn);

            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_PROCESS_SUCCESS,
                $creditTransfer->toArrayPublic());

            return $creditTransfer;
        });

        return $creditTransfer;
    }
}
