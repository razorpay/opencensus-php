<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Models\Base;
use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array        $input
     * @param PublicEntity $maker
     * @param BankingAccount\Entity $bankingAccount
     *
     * @return Entity $state
     */
    public function createForMakerAndEntity(array $input, PublicEntity $maker, BankingAccount\Entity $bankingAccount)
    {
        $state = $this->create($input);

        $makerEntityName = $maker->getEntity();

        $state->$makerEntityName()->associate($maker);

        $state->merchant()->associate($bankingAccount->merchant);

        $state->bankingAccount()->associate($bankingAccount);

        $this->repo->saveOrFail($state);

        return $state;
    }

    public function captureNewBankingAccountState(BankingAccount\Entity $bankingAccount, PublicEntity $maker)
    {
        $content = [
            Entity::STATUS      => $bankingAccount->getStatus(),
            Entity::SUB_STATUS  => $bankingAccount->getSubStatus(),
            Entity::BANK_STATUS => $bankingAccount->getBankInternalStatus()
        ];

        if ($bankingAccount->bankingAccountActivationDetails !== null)
        {
            $content['assignee_team'] = $bankingAccount->bankingAccountActivationDetails->getAssigneeTeam();
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_UPDATE_ACTIVATION_STATUS,
            [
                'id'    => $bankingAccount->getId(),
                'input' => $content,
            ]);

        $this->createForMakerAndEntity($content, $maker, $bankingAccount);
    }

    /**
     * @param array $input
     *
     * @return Entity
     */
    protected function create(array $input): Entity
    {
        $state = new Entity;

        $state->build($input);

        return $state;
    }

    /**
     * Update a state entry
     */
    public function update(string $id, array $input)
    {
        /** @var Entity $state */
        $state = $this->repo->banking_account_state->findByPublicId($id);

        $state->edit($input);

        $this->repo->saveOrFail($state);
    }
}
