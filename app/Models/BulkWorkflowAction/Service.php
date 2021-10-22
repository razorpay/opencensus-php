<?php

namespace RZP\Models\BulkWorkflowAction;

use Request;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Comment;
use RZP\Trace\TraceCode;
use RZP\Models\RiskWorkflowAction;
use RZP\Models\Card\IIN\Import\XLSFileHandler;

class Service extends Base\Service
{
    public function executeBulkAction(array $input)
    {
        return $this->core()->executeBulkAction($input);
    }

    public function addBulkRiskActionCommentPostExecution(array $input)
    {
        $this->trace->info(TraceCode::BATCH_REQUEST_RISK_ACTION_COMMENT_POST_EXECUTION, $input);

        $failedMids = [];

        $failedMidsDueToRiskConstructiveAction = [];

        $bucketType = $input[Batch\Entity::BUCKET_TYPE];

        $outputFilePath = $input[Batch\Entity::OUTPUT_FILE_PATH];

        $downloadFile = $input[Batch\Entity::DOWNLOAD_FILE];

        $filePath = (new Batch\Core())->downloadAndGetFilePath($outputFilePath, $bucketType, $downloadFile);

        $csvRows = (new XLSFileHandler)->getCsvData($filePath)['data'];

        $bulkActionId = $csvRows[1][1];

        foreach ($csvRows as $row)
        {
            if (end($row) === 'INVALIDATED' || end($row) === 'FAILED')
            {
                $failedMids[] = $row[0];
            }

            if (sizeof($row) > 3 and $row[sizeof($row) - 2] === Constants::RISK_CONSTRUCTIVE_ACTION_PERMISSION_ERROR_MESSAGE)
            {
                $failedMidsDueToRiskConstructiveAction[] = $row[0];
            }
        }

        $bulkActionResult = [
            'total_count'   => sizeof($csvRows) - 1,
            'success_count' => (sizeof($csvRows) - 1) - sizeof($failedMids),
            'failed_count'  => sizeof($failedMids),
            'failed_mids'   => $failedMids,
        ];

        if (sizeof($failedMidsDueToRiskConstructiveAction) > 0)
        {
            $bulkActionResult['failed_mids_due_to_risk_constructive_action'] = $failedMidsDueToRiskConstructiveAction;

            $bulkActionResult['failure_reason'] = 'Atleast 1 of the merchant has risk tag and hence constructive action can only be performed for these MIDs by Risk L3';
        }

        $riskWorkflowMaker = (new RiskWorkflowAction\Service())->getIndividualRiskWorkflowMaker();

        $bulkAction = $this->repo->workflow_action->findOrFailPublic($bulkActionId);

        (new Comment\Core())->createForWorkflowAction([
            'comment'   => sprintf('BULK_WORKFLOW_ACTION_STATUS: %s', json_encode($bulkActionResult)),
        ], $bulkAction, $riskWorkflowMaker);

        $bulkAction->tag(Constants::BULK_WORKFLOW_COMPLETED_TAG);

        $bulkAction->untag(Constants::BULK_WORKFLOW_IN_PROGRESS_TAG);

        $this->repo->workflow_action->saveOrFail($bulkAction);

        return $bulkActionResult;
    }
}
