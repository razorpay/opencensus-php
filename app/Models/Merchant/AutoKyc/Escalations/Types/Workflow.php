<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\AutoKyc\Escalations\Entity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Workflow\Action\Entity as WfActionEntity;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class Workflow extends BaseEscalationType
{
    public function triggerEscalation($merchants,$merchantsGmvList, string $type, int $level)
    {
        $merchantsGmvMap = collect($merchantsGmvList)->mapToDictionary(function($item, $key) {
            return [$item[DetailEntity::MERCHANT_ID] => $item[MConstants::TOTAL]];
        });
        foreach ($merchants as $merchant)
        {
            try
            {
                $this->app['basicauth']->setMerchant($merchant);
                $entity = $this->triggerWorkflow($merchant);
                $workflowId = null;
                if(empty($entity) === false)
                {
                    $workflowId = $entity[MConstants::ID];
                    WfActionEntity::verifyIdAndSilentlyStripSign($workflowId);
                }


                $escalation = (new Entity)->build([
                                                      Entity::MERCHANT_ID         => $merchant->getId(),
                                                      Entity::ESCALATION_TYPE     => $type,
                                                      Entity::ESCALATION_METHOD   => Constants::WORKFLOW,
                                                      Entity::ESCALATION_LEVEL    => $level,
                                                      Entity::WORKFLOW_ID         => $workflowId
                                                  ]);
                $this->repo->merchant_auto_kyc_escalations->saveOrFail($escalation);

                $this->app[MConstants::TRACE]->info(TraceCode::SELF_SERVE_ESCALATION_SUCCESS, [
                    'type'          => $type,
                    'level'         => $level,
                    'merchant_id'   => $merchant->getId()
                ]);

                (new EscalationV2())->createEscalationV2ForMerchant($merchant, $merchantsGmvMap[$merchant->getId()][0], $type, $level);

            }
            catch (\Exception $e)
            {
                $this->app[MConstants::TRACE]->info(TraceCode::SELF_SERVE_ESCALATION_FAILURE, [
                    'type'          => $type,
                    'level'         => $level,
                    'reason'        => 'something went wrong while handling escalation',
                    'trace'         => $e->getMessage(),
                    'merchant_id'   => $merchant->getId()
                ]);
            }
        }

    }

    private function triggerWorkflow($merchant)
    {
        $permissionName = Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH;

        $tags = [];

        if ($merchant->merchantDetail->isUnregisteredBusiness())
        {
            $permissionName = Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED;
        }

        if ($merchant->merchantDetail->merchantWebsite !== null)
        {
            $tags[] = 'tnc_generated';
        }

        $escalation = $this->repo->merchant_onboarding_escalations->fetchEscalationForThresholdAndMilestone(
            $merchant->getId(), 'L1', 1500000);

        if(empty($escalation) === false)
        {
            $tags[] = '15k_transacted_before_l2';
        }

        $actions = (new ActionCore)->fetchOpenActionOnEntityOperationWithPermissionList(
            $merchant->getId(), 'merchant_detail', [$permissionName]);
        $actions = $actions->toArray();

        if(empty($actions) === false)
        {
            // If a workflow is already created, then do not create the same workflow;
            return $actions[0];
        }

        // The reason routeName and Controller is set here because
        // the workflow being triggered is associated with the different route.
        $this->app[MConstants::WORKFLOW]
            ->setPermission($permissionName)
            ->setRouteName(DetailConstants::ACTIVATION_ROUTE_NAME)
            ->setController(DetailConstants::ACTIVATION_CONTROLLER)
            ->setWorkflowMaker($merchant)
            ->setWorkflowMakerType(MakerType::MERCHANT)
            ->setMakerFromAuth(false)
            ->setTags($tags)
            ->setRouteParams([DetailEntity::ID => $merchant->getId()])
            ->setInput([])
            ->setEntity($merchant->merchantDetail->getEntity())
            ->setOriginal([])
            ->setDirty($merchant->merchantDetail);
        try
        {
            $this->app[MConstants::WORKFLOW]->handle();
        }
        catch(Exception\EarlyWorkflowResponse $e)
        {
            // Catching exception because we do not want to abort the code flow
            $workflowActionData = json_decode($e->getMessage(), true);
            return $this->app[MConstants::WORKFLOW]->saveActionIfTransactionFailed($workflowActionData);
        }
    }
}
