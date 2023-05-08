<?php

namespace RZP\Services\FTS\Transfer;

use App;
use RZP\Models\FundTransfer\Attempt\Metric;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\FTS\Base as BaseHandler;

class Client extends BaseHandler
{
    use Recon;
    use Initiate;

    protected $app;

    public function __construct($app)
    {
        if ($app == null)
        {
            $this->app = App::getFacadeRoot();
        }
        else
        {
            $this->app = $app;
        }
        parent::__construct($this->app);
    }

    public function setRequest(array $request)
    {
        $this->request = $request;
    }

    public function doTransfer()
    {
        $this->createFTA()
             ->makePayload();

        $response = $this->app['fts_fund_transfer']->createAndSendRequest(
          parent::FUND_TRANSFER_CREATE_URI,
          Requests::POST,
          $this->request);

        $this->extractAndUpdateResponse($response);

        return $response;
    }

    public function doRecon(array $response)
    {
        try
        {
            $this->reconcileFTA($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
              $e,
              Trace::ERROR,
              TraceCode::FTS_UPDATE_FUND_TRANSFER_ATTEMPT_FAILED,
              [
                'error' => $e->getMessage()
              ]);

            $this->trace->count(Metric::WEBHOOK_UPDATE_FAILURE_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);
        }

        return $response;
    }
}
