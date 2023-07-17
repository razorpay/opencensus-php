<?php

namespace RZP\Http\Controllers;

use App;
use View;
use ApiResponse;
use Request as Req;
use RZP\Trace\TraceCode;
use Illuminate\Http\Request;
use RZP\Constants\Entity as E;

use RZP\Exception\BadRequestException;

class BatchController extends Controller
{
    public function createBatch()
    {
        $result = $this->service()->createBatch($this->input);

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForBatchUpload();
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::BATCH_SEGMENT_EVENT_PUSH_FAILED, []);
        }

        return ApiResponse::json($result);
    }

    public function getBatches()
    {
        $result = $this->service()->fetchMultiple($this->input);

        return ApiResponse::json($result);
    }

    public function getBatchById($id)
    {
        $result = $this->service()->getBatchById($id);

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingBatchDetailsFromBatchId();
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::BATCH_SEGMENT_EVENT_PUSH_FAILED, []);
        }

        return ApiResponse::json($result);
    }

    public function validateFileName()
    {
        $input = $this->input;

        $result = $this->service()->validateFileName($input);

        return ApiResponse::json($result);
    }

    public function processBatches()
    {
        $result = $this->service()->processBatches();

        return ApiResponse::json($result);
    }

    public function processBatch(string $id)
    {
        $result = $this->service()->processBatch($id, $this->input);

        return ApiResponse::json($result);
    }

    /**
     * Ref: Batch/Core::retryBatchOutputFile
     *
     * @param string $id
     *
     * @return ApiResponse
     */
    public function retryBatchOutputFile(string $id)
    {
        $result = $this->service()->retryBatchOutputFile($id);

        return ApiResponse::json($result);
    }

    public function downloadBatch($id)
    {
        $result = $this->service()->downloadBatch($id);

        return ApiResponse::json($result);
    }

    public function validateFile()
    {
        $response = $this->service()->validateFile($this->input);

        return ApiResponse::json($response);
    }

    public function getStats(string $id)
    {
        $response = $this->service()->fetchStatsOfBatch($id);

        return ApiResponse::json($response);
    }

    public function renderBatchUploadForm(Request $request)
    {
        $input = $request->all();

        $isValid = $this->service()->validateToken($input);

        if ($isValid === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BATCH_UPLOAD_INVALID_TOKEN);
        }
        else
        {
            $view = View::make('public.direct_debit_form', [
                'dashboardHost' =>   $this->getDashboardHost($input),
            ]);
        }

        return $view;
    }

    public function validateBatchFile(Request $request)
    {
        $input = $request->all();
        $this->service()->consumeToken($input);
        $response = $this->service()->validateFile($input);

        return ApiResponse::json($response);
    }

    /**
     * @param Request $request
     *
     * After Batch is processed, Batch Service will
     * call batch/sendmail route to trigger mail
     * to merchant about batch completion.
     *
     * @return mixed
     */
    public function sendMail(Request $request)
    {
        $input = $request->all();

        $response = $this->service()->sendMail($input);

        return ApiResponse::json($response);
    }

    /**
     * @param Request $request
     *
     * After Batch is processed, Batch Service will
     * call batch/sendsms route to trigger sms
     * to merchant about batch completion.
     *
     * @return mixed
     */
    public function sendSMS(Request $request)
    {
        $input = $request->all();

        $response = $this->service()->sendSMS($input);

        return ApiResponse::json($response);
    }

    /**
     * @param $path
     *
     * @return array
     *
     *  Redirects to batch Micro Service.
     */
    public function sendRequest($path)
    {
        $method = Req::method();

        $input = Req::all();

        $options['mode'] = $this->app['rzp.mode'];

        $response = $this->app->batchService->getResponseFromBatchService($path, $method, $options, $input);

        return $response;
    }

    /**
     * @param array $input
     * @return string
     */
    public function getDashboardHost(array $input): string
    {
        $defaultDashboardURL =  config("applications.dashboard.url");

        try
        {
            $merchant = $this->service(E::MERCHANT_REQUEST)->retrieveMerchantByToken($input['token']);

            if(empty($merchant) === true || $merchant->isRazorpayOrgId() === true)
            {
                return $defaultDashboardURL;
            }

            $orgHost = $merchant->org->getPrimaryHostName();

            return $this->addHttps($orgHost);
        }
        catch (\Exception $exception)
        {
            return $defaultDashboardURL;
        }
    }

    /**
     * Prepend https to the provided host.
     *
     * @param string $host
     * @return string
     */
    public function addHttps(string $host): string
    {
        return "https://" . $host;
    }
}
