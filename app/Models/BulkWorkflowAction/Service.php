<?php

namespace RZP\Models\BulkWorkflowAction;

use Request;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Comment;
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
        $failedMids = [];

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
        }

        $bulkActionResult = [
            'total_count'   => sizeof($csvRows) - 1,
            'success_count' => (sizeof($csvRows) - 1) - sizeof($failedMids),
            'failed_count'  => sizeof($failedMids),
            'failed_mids'   => $failedMids,
        ];

        $riskWorkflowMaker = (new RiskWorkflowAction\Service())->getIndividualRiskWorkflowMaker();

        $bulkAction = $this->repo->workflow_action->findOrFailPublic($bulkActionId);

        (new Comment\Core())->createForWorkflowAction([
            'comment'   => sprintf('BULK_WORKFLOW_ACTION_STATUS: %s', json_encode($bulkActionResult)),
        ], $bulkAction, $riskWorkflowMaker);

        return $bulkActionResult;
    }
}
