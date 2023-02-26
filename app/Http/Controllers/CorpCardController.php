<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Trace\TraceCode;

class CorpCardController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function onboardCapitalCorpCardForRzpX()
    {
        $input = Request::all();

        $this->trace->info(
            TraceCode::CORP_CARD_ONBOARDING,
            [
                'input' => $input,
            ]
        );
        
        $response = $this->service()->onboardCapitalCorpCardForPayouts($input);

        return ApiResponse::json($response);
    }
}
