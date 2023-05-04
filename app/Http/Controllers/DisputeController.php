<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\DisputesClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Dispute\Chargeback\Service as DisputeChargebackService;

class DisputeController extends Controller
{
    use Traits\HasCrudMethods;

    /**
     * {@inheritDoc}
     * Overridden as it passes around $input to service method
     */

    public $disputeBulkCronTimeout = 600;

    protected function addLogsForDisputeServiceFailure($response)
    {
        $this->trace->error(TraceCode::DISPUTES_INTEGRATION_ERROR, [
            'expected_response' => $response,
        ]);

        $this->trace->count(Metric::DISPUTES_SERVICE_ERROR_COUNT);
    }

    protected function getDisputeServiceResponse(): array
    {
        $response = null;

        try
        {
            $featureFlag = sprintf("%s_%s", RazorxTreatment::DISPUTES_DECOMP, $this->app['api.route']->getCurrentRouteName());

            $variant = $this->app['razorx']->getTreatment($this->app['request']->getId(), $featureFlag, $this->app['basicauth']->getMode() ?? Mode::LIVE);

            if ($variant === RazorxTreatment::RAZORX_VARIANT_ON)
            {
                $response = $this->app['disputes']->forwardToDisputesService();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::DISPUTES_INTEGRATION_ERROR, [
                'error_message' => $e->getMessage(),
                'response' => $response,
                'auth_type' => $this->ba->getAuthType(),
                'merchant_id' => $this->ba->getMerchantId() ?? 'none',
            ]);

            throw $e;
        }
        return [$response, $variant];
    }

    public function get(string $id)
    {
        $response = null;
        $variant = null;

        try
        {
            [$response, $variant] = $this->getDisputeServiceResponse();
        }
        catch (\Throwable $e)
        {
            $response = $this->service()->fetch($id, $this->input);

            $this->addLogsForDisputeServiceFailure($response);
        }

        if ($variant !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $response = $this->service()->fetch($id, $this->input);
        }

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingDisputeDetailsFromDisputeId();
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::DISPUTE_SEGMENT_EVENT_PUSH_FAILED, []);
        }

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
        $response = null;
        $variant = null;

        try
        {
            [$response, $variant] = $this->getDisputeServiceResponse();
        }
        catch (\Throwable $e)
        {
            $input = Request::all();

            $response = $this->service()->fetchMultiple($input);

            $this->addLogsForDisputeServiceFailure($response);
        }

        if ($variant !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $input = Request::all();

            $response = $this->service()->fetchMultiple($input);
        }

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingDisputeDetails($input);
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::DISPUTE_SEGMENT_EVENT_PUSH_FAILED, []);
        }

        return ApiResponse::json($response);
    }

    public function getCountForFetchMultiple()
    {
        $response = null;
        $variant = null;

        try
        {
            [$response, $variant] = $this->getDisputeServiceResponse();
        }
        catch (\Throwable $e)
        {
            $input = Request::all();

            $response = $this->service()->getCountForFetchMultiple($input);

            $this->addLogsForDisputeServiceFailure($response);
        }

        if ($variant !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $input = Request::all();

            $response = $this->service()->getCountForFetchMultiple($input);
        }

        return ApiResponse::json($response);
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
        $response = null;
        $variant = null;

        try
        {
            [$response, $variant] = $this->getDisputeServiceResponse();
        }
        catch (\Throwable $e)
        {
            $response = $this->service()->fetchDisputeReasonInternal($disputeReasonId);

            $this->addLogsForDisputeServiceFailure($response);
        }

        if ($variant !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $response = $this->service()->fetchDisputeReasonInternal($disputeReasonId);
        }

        return ApiResponse::json($response);
    }

    public function initiateMerchantEmails()
    {
        RuntimeManager::setTimeLimit($this->disputeBulkCronTimeout);

        RuntimeManager::setMaxExecTime($this->disputeBulkCronTimeout);

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
        $response = null;
        $variant = null;

        try
        {
            [$response, $variant] = $this->getDisputeServiceResponse();
        }
        catch (\Throwable $e)
        {
            $response =  $this->service()->getDisputeDocumentTypesMetadata();

            $this->addLogsForDisputeServiceFailure($response);
        }

        if ($variant !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $response =  $this->service()->getDisputeDocumentTypesMetadata();
        }

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

    public function deductionReversalCron()
    {
        $response = $this->service()->deductionReversalCron();

         return ApiResponse::json($response);
     }

    public function postBatchChargebackAutomation($gateway)
    {
        $input = Request::all();

        $response = (new DisputeChargebackService())->postBatchChargebackAutomation($input, $gateway);

        return ApiResponse::json($response);
    }
}
