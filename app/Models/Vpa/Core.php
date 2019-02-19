<?php

namespace RZP\Models\Vpa;

use Razorpay\Trace\Logger as Trace;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Jobs\FTS\CreateAccount;
use RZP\Models\FundAccount\Type;

class Core extends Base\Core
{
    public function createForBankingSource(array $input, Base\PublicEntity $source): Entity
    {
        $vpa = (new Entity)->build($input);

        /** @var Merchant\Entity $merchant */
        $merchant = $source->merchant;

        $vpa->merchant()->associate($merchant);

        $vpa->entity()->associate($source);

        $this->repo->saveOrFail($vpa);

        $this->callFtsCreateAccount($vpa);

        return $vpa;
    }

    protected function callFtsCreateAccount(Entity $vpa)
    {
        try
        {
            $id = $vpa->getId();

            $sourceType = $vpa->getEntityType();

            switch ($sourceType)
            {
                case Constants\Entity::CONTACT:
                    CreateAccount::dispatch($this->mode, $id, Type::VPA, Constants\Entity::PAYOUT);

                    break;
            }

            $this->trace->info(
                TraceCode::FTS_CREATE_ACCOUNT_JOB_DISPATCHED,
                [
                    'vpa_id'      => $id,
                    'source_type' => $sourceType,
                ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_CREATE_ACCOUNT_DISPATCH_FAILED,
                [
                    'vpa_id'      => $vpa->getId(),
                    'source_type' => $vpa->getEntityType(),
                ]);
        }
    }

    public function getVpaEntity($id)
    {
        return $this->repo->vpa->findOrFailPublic($id);
    }

    public function updateVpaWithFtsId(Entity $entity, $ftsFundAccountId)
    {
        $entity->setFtsFundAccountId($ftsFundAccountId);

        $this->repo->saveOrFail($entity);
    }
}
