<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\InternationalIntegration\Emerchantpay\EmerchantpayApmRequestFile;
use RZP\Models\Merchant\OwnerDetail\Validator as OValidator;
use RZP\Trace\TraceCode;

class MerchantApmEnablementController extends Controller
{
    public function getEmerchantpayRequestData()
    {
        $data = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)->fetchEmerchantpayRequestData();
        return ApiResponse::json($data);
    }

    public function postEmerchantpayRequestData()
    {
        $input = Request::all();
        $data = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->createOrUpdateEmerchantpayRequestData($input);

        return ApiResponse::json($data);
    }

    public function deleteEmerchantpayRequestOwner()
    {
        $input = Request::all();
        (new OValidator)->validateInput('delete_by_api', $input);

        $data = $this->service(E::MERCHANT_OWNER_DETAILS)
            ->deleteOwnerDetail($input['owner_id']);

        return ApiResponse::json($data);
    }

    public function generateEmerchantpayMaf($mode,$mid)
    {
        $input = Request::all();

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $this->trace->info(
            TraceCode::EMERCHANTPAY_APM_REQUEST_MAF_GENERATE,
            [
                'mid' => $mid,
                'mode' => $mode,
            ]
        );

        $input['merchant_id'] = $mid;

        $fileProcessor = new EmerchantpayApmRequestFile;
        $ufhResponse = $fileProcessor->generate($input, null, null);
        if(count($ufhResponse) !== 0)
        {
            $fileProcessor->sendGifuFile();
        }

        $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->postProcessEmerchantpayMaf($mid);

        return $ufhResponse;
    }
}
