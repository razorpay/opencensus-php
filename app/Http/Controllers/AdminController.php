<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use App;
use Redirect;
use Request;
use RZP\Models\Admin;

class AdminController extends Controller
{
    protected $service = Admin\Service::class;

    public function getEntities()
    {
        $input = Request::all();

        $data = (new Admin\Service)->getAllEntities($input);

        return ApiResponse::json($data);
    }

    public function getEntityMultiple($type)
    {
        $input = Request::all();

        $data = $this->service()->fetchMultipleEntities($type, $input);

        return ApiResponse::json($data);
    }

    public function getTerminalById($id)
    {
        // For Single Terminal fetch we want to display sub merchants
        // Setting sub_merchant will give sub_merchant associated with terminal
        $subMerchantFlag = true;

        $type = 'terminal';

        $data = $this->service()->fetchTerminalEntityByIdWithFlag($type, $id, $subMerchantFlag);

        return ApiResponse::json($data);
    }

    public function getEntityById($type, $id)
    {
        $input = Request::all();

        $data = $this->service()->fetchEntityById($type, $id, $input);

        return ApiResponse::json($data);
    }

    public function postSendTestNewsletter()
    {
        $input = Request::all();

        $data = $this->service()->sendTestNewsletter($input);

        return ApiResponse::json($data);
    }

    public function postSendNewsletter()
    {
        $input = Request::all();

        $data = $this->service()->sendNewsletter($input);

        return ApiResponse::json($data);
    }

    public function getTransparentRedirect()
    {
        $input = Request::all();

        if (isset($input['url']))
        {
            $url = $input['url'];
            unset($input['url']);

            $query = http_build_query($input);
            $url .= '?'.$query;

            return Redirect::to($url);
        }
    }

    public function postTransparentRedirect()
    {
        $input = Request::all();
    }

    public function setConfigKeys()
    {
        $input = Request::all();

        $data = $this->service()->setConfigKeys($input);

        return ApiResponse::json($data);
    }

    public function getConfigKeys()
    {
        $data = $this->service()->getConfigKeys();

        return ApiResponse::json($data);
    }

    public function getScorecard()
    {
        $input = Request::all();

        $data = $this->service()->generateScorecard($input);

        return ApiResponse::json($data);
    }

    public function postMailgunCallback($type)
    {
        $input = Request::all();

        $responseStatus = $this->service()->processMailgunCallback($type, $input);

        return ApiResponse::json([], $responseStatus);
    }

    public function updateEntityTax($entity)
    {
        $input = Request::all();

        $limit = $input['limit'];

        $data = $this->service()->updateTaxColumnValue($entity, $limit);

        return ApiResponse::json($data);
    }
}
