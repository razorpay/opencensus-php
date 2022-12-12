<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Exception;
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

    const DISPUTES_SERVICE_RELEASE_TIMESTAMP = 1673326800;  // 10 January 2023

    protected function isWrittenToDisputeService(array $createdAtRes): bool
    {
        return (sizeof($createdAtRes) === 1
        && isset($createdAtRes[0][Entity::CREATED_AT])
        && $createdAtRes[0][Entity::CREATED_AT] >= self::DISPUTES_SERVICE_RELEASE_TIMESTAMP);
    }

    public function get(string $id)
    {
        $variant = $this->app['razorx']->getTreatment($this->ba->getMerchantId(), RazorxTreatment::DISPUTES_DECOMP, $this->app['basicauth']->getMode() ?? Mode::LIVE);

        if ($variant === RazorxTreatment::RAZORX_VARIANT_ON)
        {
            $createdAtRes = $this->service()->getCreatedAtFromDisputeId($id);

            // A GET request can be served by the disputes service only if the data has flown to it via a Dual-write from API DB.
            if ($this->isWrittenToDisputeService($createdAtRes) === true)
            {
                $response = null;

                try
                {
                    $response = $this->app['disputes']->forwardToDisputesService();
                }
                catch (\Throwable $e)
                {
                    $this->trace->error(TraceCode::DISPUTES_INTEGRATION_ERROR, [
                        'error_message' => $e->getMessage(),
                        'response'      => $response,
                        'auth_type'     => $this->ba->getAuthType(),
                        'merchant_id'   => $this->ba->getMerchantId() ?? 'none',
                    ]);

                    $this->trace->count(Metric::DISPUTES_SERVICE_ERROR_COUNT);

                    // handling fallbacks for the new dispute service temporarily by calling
                    // the service in case of failures and emitting prom metrics, logs.
                    $response = $this->service()->fetch($id, $this->input);

                    $this->trace->error(TraceCode::DISPUTES_INTEGRATION_ERROR, [
                        'expected_response' => $response,
                    ]);
                }
            }
            else
            {
                $response = $this->service()->fetch($id, $this->input);
            }
        }
        else
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
        $input = Request::all();

        $disputes = $this->service()->fetchMultiple($input);

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
