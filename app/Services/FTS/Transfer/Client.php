<?php

namespace RZP\Services\FTS\Transfer;

use App;
use RZP\Http\Request\Requests;
use RZP\Services\FTS\Base as BaseHandler;

class Client extends BaseHandler
{
    use Initiate;

    protected $app;

    public function __construct($app)
    {
        if ($app == null)
        {
            $this->app = App::getFacadeRoot();
        } else
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
}
