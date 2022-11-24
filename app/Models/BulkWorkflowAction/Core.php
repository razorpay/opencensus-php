<?php


namespace RZP\Models\BulkWorkflowAction;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\Comment;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\RiskWorkflowAction;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Workflow\Action as Action;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function handleBulkAction(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_BULK_RISK_ACTION_UPDATE_REQUEST, ['data' => $input]);

        (new RiskWorkflowAction\Core())->validateRiskAttributes($input);

        $this->createBulkActionWorkflow($input);
    }

    private function createBulkActionWorkflow(array $input)
    {
        $action = $input['action'];

        $entityId = UniqueIdEntity::generateUniqueId();

        $tags[] = sprintf("%s%s", RiskWorkflowAction\Constants::BULK_WORKFLOW_GROUP_TAG_PREFIX, $entityId);

        $this->trace->info(TraceCode::CREATE_BULK_EDIT_WORKFLOW,
            [
                'data'          => $input,
                'action'        => $action,
                'tags'          => $tags,
                'entity_id'     => $entityId,
            ]);

        $input['entity_id'] = $entityId;

        $this->app['workflow']
            ->setPermission(Constants::BULK_WORKFLOW_ACTION_PERMISSION_NAME[$action])
            ->setRouteName(Constants::BULK_WORKFLOW_ACTION_ROUTE_NAME)
            ->setController(Constants::BULK_WORKFLOW_ACTION_ROUTE_CONTROLLER)
            ->setMakerFromAuth(true)
            ->setEntityAndId(E::BULK_WORKFLOW_ACTION, $entityId)
            ->setTags($tags)
            ->setInput($input)
            ->handle(null, $input);
    }

    public function executeBulkAction(array $input)
    {
        $this->trace->info(TraceCode::EXECUTE_BULK_RISK_ACTION,
           [
               'input'=> $input,
           ]);

        $entityId = $input['entity_id'];

        $action = (new Action\Core)
                ->fetchOpenActionOnEntityOperation(
                $entityId, E::BULK_WORKFLOW_ACTION, Constants::BULK_WORKFLOW_ACTION_PERMISSION_NAME[$input['action']])
                ->first();

        return $this->editBulkAction($action, $input);
    }

    private function editBulkAction(Action\Entity $action, array $input)
    {
        $merchantIds = $input['merchant_ids'];

        $actionId = $action->getId();

        $riskActionEntries = [];

        foreach ($merchantIds as $merchantId)
        {
            $csvRow = [
                RiskWorkflowAction\Constants::MERCHANT_ID               => $merchantId,
                RiskWorkflowAction\Constants::BULK_WORKFLOW_ACTION_ID   => $actionId,
            ];

            $riskActionEntries []= $csvRow;
        }

        $url = $this->createCsvFile($riskActionEntries, 'action_file', null, 'files/batch');

        $uploadedFile = new UploadedFile(
            $url,
            'action_file.csv',
            'text/csv',
            null,
            true);

        $params = [
            'file'  => $uploadedFile,
            'type'  => 'create_exec_risk_action',
        ];

        $batchResult = (new Batch\Core)->create($params, (new Merchant\Core())->get('100000Razorpay'));

        (new Comment\Service())->createForWorkflowAction([
            'comment'   => sprintf(Constants::BATCH_STATUS_TPL, $batchResult['id']),
        ], Action\Entity::getSignedId($actionId));

        $action->tag(Constants::BULK_WORKFLOW_IN_PROGRESS_TAG);

        $this->repo->workflow_action->saveOrFail($action);

        return $batchResult;
    }
}
