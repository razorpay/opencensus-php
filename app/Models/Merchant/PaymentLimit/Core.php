<?php


namespace RZP\Models\Merchant\PaymentLimit;

use RZP\Constants\Entity as E;
use RZP\Models\Base;

use RZP\Models\Card\IIN\Import\XLSFileHandler;
use RZP\Trace\TraceCode;
use RZP\Models\Workflow\Action as Action;
use RZP\Models\Batch;
use RZP\Models\Merchant;
use RZP\Models\Comment;
use RZP\Models\FileStore\Type as FileStoreType;

class Core extends Base\Core
{
    public function handleBulkAction(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_BULK_MAX_PAYMENT_LIMIT_UPDATE_ACTION_REQUEST, ['data' => $input]);

        $this->createBulkActionWorkflow($input);
    }

    private function createBulkActionWorkflow(array $input)
    {
        $action = $input['action'];

        $entityId = $input["entity_id"];

        $this->trace->info(TraceCode::CREATE_BULK_MAX_PAYMENT_LIMIT_UPDATE_WORKFLOW,
            [
                'data' => $input,
                'action' => $action,
                'entity_id' => $entityId,
            ]);

        $workflowAction = $this->app['workflow']
            ->setPermission(Constants::BULK_MAX_PAYMENT_WORKFLOW_ACTION_PERMISSION_NAME[$action])
            ->setRouteName(Constants::BULK_MAX_PAYMENT_WORKFLOW_ACTION_ROUTE_NAME)
            ->setController(Constants::BULK_MAX_PAYMENT_WORKFLOW_ACTION_ROUTE_CONTROLLER)
            ->setMakerFromAuth(true)
            ->setEntityAndId(E::PAYMENT_LIMIT, $entityId)
            ->setInput($input)
            ->handle(null, $input);

        $this->trace->info(TraceCode::BULK_MAX_PAYMENT_LIMIT_UPDATE_WORKFLOW_CREATED,
            [
                'data' => $input,
                'workflow_id' => $workflowAction['id'],
            ]);
        return $workflowAction['id'];
    }

    public function executeMaxPaymentLimitWorkflow(array $input)
    {
        $this->trace->info(TraceCode::EXECUTE_BULK_MAX_PAYMENT_LIMIT_UPDATE_ACTION,
            [
                'input' => $input,
            ]);

        $entityId = $input['entity_id'];

        $action = (new Action\Core)
            ->fetchOpenActionOnEntityOperation(
                $entityId, E::PAYMENT_LIMIT, Constants::BULK_MAX_PAYMENT_WORKFLOW_ACTION_PERMISSION_NAME[$input['action']])
            ->first();

        return $this->editBulkAction($action, $input);

    }

    private function editBulkAction(Action\Entity $action, array $input)
    {
        $this->trace->info(TraceCode::EDIT_BULK_MAX_PAYMENT_LIMIT_UPDATE_ACTION,
            [
                'input' => $input,
                'action' => $action,
            ]);

        $outputFilePath = $input['file_path'];

        $bucketType = FileStoreType::PAYMENT_LIMIT;

        $downloadFile = true;

        $filePath = (new Batch\Core())->downloadAndGetFilePath($outputFilePath, $bucketType, $downloadFile);

        $csvRows = (new XLSFileHandler)->getCsvData($filePath)['data'];

        $merchantIdsFailed = [];

        $merchantUpdateFailed = true;

        $this->trace->info(TraceCode::EDIT_BULK_MAX_PAYMENT_LIMIT_CSV_FETCHED,
            [
                'csvRows' => $csvRows
            ]);

        for ($row = 1; $row < count($csvRows); $row++) {
            try {
                $merchantId = $csvRows[$row][0];
                $maxPaymentLimit = $csvRows[$row][1];
                $maxInternationalPaymentLimit = $csvRows[$row][2];

                if (empty($merchantId) === false) {
                    $merchant = (new Merchant\Core())->get($merchantId);

                    if (empty($maxPaymentLimit) === false) {
                        $merchant->setMaxPaymentAmount($maxPaymentLimit);
                        $merchantUpdateFailed = false;
                    }
                    if (empty($maxInternationalPaymentLimit) === false) {
                        $merchant->setMaxInternationalPaymentAmount($maxInternationalPaymentLimit);
                        $merchantUpdateFailed = false;
                    }
                }

                if ($merchantUpdateFailed === true) {
                    $merchantIdsFailed[] = $merchantId;
                } else {
                    $this->repo->saveOrFail($merchant);
                }

            } catch (\Throwable $ex) {
                $this->trace->error(TraceCode::BULK_MAX_PAYMENT_LIMIT_UPDATE_ERROR, [
                    "csv_row_entry" => $csvRows[$row],
                    "error" => $ex
                ]);
                $merchantIdsFailed[] = $merchantId;
            }
        }

        $paymentLimitUpdateResult = [
            'total_count' => sizeof($csvRows) - 1,
            'success_count' => (sizeof($csvRows) - 1) - sizeof($merchantIdsFailed),
            'failed_count' => sizeof($merchantIdsFailed),
            'failed_mids' => $merchantIdsFailed,
        ];

        $admin = $this->app['basicauth']->getAdmin();

        (new Comment\Core())->createForWorkflowAction([
            'comment' => sprintf('BULK_MAX_PAYMENT_WORKFLOW_RESULT : %s', json_encode($paymentLimitUpdateResult)),
        ], $action, $admin);

        return $paymentLimitUpdateResult;

    }

}
