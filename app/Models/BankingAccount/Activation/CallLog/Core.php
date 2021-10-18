<?php


namespace RZP\Models\BankingAccount\Activation\CallLog;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount as BA;
use RZP\Models\Admin\Admin;

class Core extends Base\Core
{
    public function create(BA\Entity $bankingAccount, Admin\Entity $admin, BA\State\Entity $state, array $input, BA\Activation\Comment\Entity $comment = null): Entity
    {
        $input[Entity::ADMIN_ID] = $admin->getId();

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $input[Entity::STATE_LOG_ID] = $state->getId();

        if (empty($comment) === false)
        {
            $input[Entity::COMMENT_ID] = $comment->getId();
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_CALL_LOG_CREATE,
                           [
                               'input' => $input,
                           ]);

        $newCallLogEntity = new Entity;

        $newCallLog = $newCallLogEntity->build($input);

        $newCallLog->admin()->associate($admin);

        $newCallLog->bankingAccount()->associate($bankingAccount);

        $newCallLog->stateLog()->associate($state);

        if (empty($comment) === false)
        {
            $newCallLog->comment()->associate($comment);
        }

        $newCallLog->saveOrFail();

        return $newCallLog;
    }

    public function update(Entity $callLog, array $input): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CALL_LOG_UPDATE,
            [
                'banking_account_id'    => $callLog->bankingAccount->getId(),
                'input' => $input,
            ]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $callLog->edit($input);

        $this->repo->saveOrFail($callLog);

        return $callLog;
    }
}
