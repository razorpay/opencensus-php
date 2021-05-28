<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;

use RZP\Exception;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\AutoKyc\Escalations\Entity;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Workflow\Action\MakerType;

class Workflow extends BaseEscalationType
{
    public function triggerEscalation($merchants, string $type, int $level)
    {
        foreach ($merchants as $merchant)
        {
            $entity = $this->triggerWorkflow($merchant);

            $escalation = (new Entity)->build([
                Entity::MERCHANT_ID         => $merchant->getId(),
                Entity::ESCALATION_TYPE     => $type,
                Entity::ESCALATION_METHOD   => Constants::WORKFLOW,
                Entity::ESCALATION_LEVEL    => $level,
                Entity::WORKFLOW_ID         => $entity['entity_id']
            ]);
            $this->repo->merchant_auto_kyc_escalations->saveOrFail($escalation);
        }
    }

    private function triggerWorkflow($merchant)
    {
        $input = [DetailEntity::ACTIVATION_STATUS => Status::ACTIVATED];

        $permissionName = Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH;

        $tags = [];

        if ($merchant->merchantDetail->isUnregisteredBusiness())
        {
            $permissionName = Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED;
        }

        if ($merchant->merchantDetail->tnc !== null)
        {
            $tags[] = 'tnc_generated';
        }

        // The reason routeName and Controller is set here because
        // the workflow being triggered is associated with the different route.
        $this->app['workflow']
            ->setPermission($permissionName)
            ->setRouteName(DetailConstants::ACTIVATION_ROUTE_NAME)
            ->setController(DetailConstants::ACTIVATION_CONTROLLER)
            ->setWorkflowMaker($merchant)
            ->setWorkflowMakerType(MakerType::MERCHANT)
            ->setMakerFromAuth(false)
            ->setTags($tags)
            ->setRouteParams([DetailEntity::ID => $merchant->getId()])
            ->setInput($input);
        try
        {
            (new DetailCore)->updateActivationStatus($merchant, $input, $merchant);
        }
        catch(Exception\EarlyWorkflowResponse $e)
        {
            // Catching exception because we do not want to abort the code flow
            $workflowActionData = json_decode($e->getMessage(), true);
            return $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
        }
    }
}
