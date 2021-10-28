<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Dispute\Chargeback\Service as DisputeChargebackService;

class DisputeController extends Controller
{
    use Traits\HasCrudMethods;

    /**
     * {@inheritDoc}
     * Overridden as it passes around $input to service method
     */
    public function get(string $id)
    {
        $response = $this->service()->fetch($id, $this->input);

        return ApiResponse::json($response);
    }

    public function create(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service()->create($input, $paymentId);

        return ApiResponse::json($data);
    }

    public function bulkCreate()
    {
        $input = Request::all();

        $data = $this->service()->bulkCreate($input);

        return ApiResponse::json($data);
    }

    public function bulkUpdate()
    {
        $input = Request::all();

        $data = $this->service()->bulkUpdate($input);

        return ApiResponse::json($data);
    }

    public function fetchMultiple()
    {
        $input = Request::all();

        $disputes = $this->service()->fetchMultiple($input);

        return ApiResponse::json($disputes);
    }

    public function getCountForFetchMultiple()
    {
        $input = Request::all();

        $disputes = $this->service()->getCountForFetchMultiple($input);

        return ApiResponse::json($disputes);
    }

    public function migrateOldAdjustments()
    {
        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain the excel file to be processed'
            );
        }

        $file = Request::file('file');

        $data = $this->service()->migrateOldAdjustments($file);

        return ApiResponse::json($data);

    }

    public function createReason()
    {
        $input = Request::all();

        $data = $this->service()->createReason($input);

        return ApiResponse::json($data);
    }

    public function deleteFile(string $id, string $fileId)
    {
        $this->service()->deleteFile($id, $fileId);

        return ApiResponse::json([], 204);
    }

    public function getFiles(string $id)
    {
        $data = $this->service()->getFiles($id);

        return ApiResponse::json($data);
    }

    public function getDefaultCreationEmails(string $merchantId)
    {
        $data = $this->service()->getDefaultDisputeEmails($merchantId);

        return ApiResponse::json($data);
    }

    public function getReasonInternal(string $disputeReasonId)
    {
        $data = $this->service()->fetchDisputeReasonInternal($disputeReasonId);

        return ApiResponse::json($data);
    }

    public function initiateMerchantEmails()
    {
        return $this->service()->initiateMerchantEmails();
    }

    public function processDisputeRefunds()
    {
        $input = Request::all();

        return $this->service()->processDisputeRefunds($input);
    }

    public function initiateRiskAssessment()
    {
        return $this->service()->initiateRiskAssessment();
    }

    public function getDisputeDocumentTypesMetadataDescription()
    {
        $response =  $this->service()->getDisputeDocumentTypesMetadata();

        return ApiResponse::json($response);
    }

    public function patchDisputeContestById($disputeId)
    {
        $input = Request::all();

        $response = $this->service()->patchDisputeContestById($disputeId, $input);

        return ApiResponse::json($response);
    }

    public function postDisputeAcceptById($disputeId)
    {
        $input = Request::all();

        $response = $this->service()->postDisputeAcceptById($disputeId, $input);

        return ApiResponse::json($response);
    }

    public function postBatchChargebackAutomation($gateway)
    {
        $input = Request::all();

        $response = (new DisputeChargebackService())->postBatchChargebackAutomation($input, $gateway);

        return ApiResponse::json($response);
    }
}
