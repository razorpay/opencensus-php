<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController {

	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (is_null($this->layout) === false)
        {
            $this->layout = view($this->layout);
        }
    }

    protected function checkMode($mode)
    {
        if ($mode !== 'live' and $mode !== 'test')
        {
            throw new \Exception('Invalid Mode');
        }
    }

    /**
     * This is to add access-token, refresh-token and client-id to headers
     * @param $data
     * @param $error
     * @return array|null
     */
    public function getMobileOauthHeaders(& $data = null, & $error = null)
    {
        $headers = [];

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $genericController = new GenericController();

            foreach ($genericController::ACCESS_TOKENS as $accessToken)
            {
                $dataKey = str_replace("-","_",$accessToken);

                if (empty($data[$dataKey]) === false)
                {
                    $headers[$accessToken] = $data[$dataKey];
                }

                unset($data[$dataKey]);
            }

            $accessToken2fa = $error[0]['_internal']['user_details']['access_token_2fa'] ?? null;

            if (empty($accessToken2fa) === false)
            {
                unset($error[0]['_internal']['user_details']['access_token_2fa']);

                $headers['x-mobile-access-token'] = $accessToken2fa;
            }

            return $headers;
        }

        return null;
    }
}
