<?php


namespace App\Http\Controllers;

use App;
use Auth;
use Input;
use Config;
use Request;
use Session;

use App\Generic;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\Admin\ApiRequestAny;

class GenerateTokenController extends Controller
{
    protected function generateToken()
    {
        $app = App::getFacadeRoot();
        $app['trace']->info(TraceCode::CAPITAL_CARDS_GENERATE_TOKEN,
            ['request' => 'Cards Generate Token']);
        $request = new ApiRequestAny([
            'client_type' => 'merchant'
        ]);

        list($error, $data, $httpCode) = $request->send("capital_cards/token", 'GET');
        $app['trace']->info(TraceCode::CAPITAL_CARDS_GENERATE_TOKEN,
            [
                'error' => $error,
                'httpCode' => $httpCode
            ]);
        if ($httpCode == 200) {
            return redirect(ApiUrl::getCheckoutApi() . "virtual-card?token=" . $data['token']);
        }
        return AppResponse::jsonResponse($error, $data, $httpCode);

    }
}
