<?php

use Http\ApiResponse;
use Models\Merchant;
use Models\Terminal;
use Models\Key;

class MerchantController extends BaseController
{
    public function postCreateMerchant()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function putMerchant($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getMerchant($id)
    {
        $data = (new Merchant\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getMerchants()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postCreateKeys($merchantId)
    {
        $data = (new Merchant\Service)->createKey($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeys($merchantId)
    {
        $data = (new Merchant\Service)->fetchKeys($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeySecret($keyId)
    {
        $data = (new Key\Core)->getKeySecret($keyId);

        return ApiResponse::json($data);
    }

    public function putKeys($merchantId, $keyId)
    {
        $input = Input::all();

        $keys = (new Merchant\Service)->updateKey($merchantId, $keyId, $input);

        return ApiResponse::json($keys);
    }

    public function postAssignPricingPlan($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->assignPricingPlan($id, $input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = (new Merchant\Service)->getPricingPlan($id);

        return ApiResponse::json($data);
    }

    public function postCreateTerminal($id)
    {
        $input = Input::all();

        $data = (new Terminal\Service)->createTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function getTerminals($mid)
    {
        $data = (new Terminal\Service)->getTerminals($mid);

        return ApiResponse::json($data);
    }

    public function getTerminal($mid, $tid)
    {
        $data = (new Terminal\Service)->getTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function deleteTerminal($mid, $tid)
    {
        $data = (new Terminal\Service)->deleteTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function putTerminal($mid, $tid)
    {
        $input = Input::all();

        $data = (new Terminal\Service)->modifyTerminal($mid, $tid, $input);

        return ApiResponse::json($data);
    }

    public function postActivate($id)
    {
        $data = (new Merchant\Service)->activate($id);

        return ApiResponse::json($data);
    }

    public function postLiveEnable($id)
    {
        $data = (new Merchant\Service)->liveEnable($id);

        return ApiResponse::json($data);
    }

    public function postLiveDisable($id)
    {
        $data = (new Merchant\Service)->liveDisable($id);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccount($id)
    {
        $data = (new Merchant\Service)->getBankAccount($id);

        return ApiResponse::json($data);
    }

    public function getBanksPublic()
    {
        $data = (new Merchant\Service)->getEnabledBanks();

        return ApiResponse::json($data);
    }

    public function getBanks($id)
    {
        $data = (new Merchant\Service)->getBanks($id);

        return ApiResponse::json($data);
    }

    public function setBanks($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->setPaymentBanks($id, $input);

        return ApiResponse::json($data);
    }

    public function putBanksForAllMerchants()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->setBanksForAllMerchants($input);
    }

    public function getBalance($id)
    {
        $data = (new Merchant\Service)->fetchBalance($id);

        return ApiResponse::json($data);
    }

    public function getPaymentMethods()
    {
        $data = (new Merchant\Service)->getPaymentMethods();

        return ApiResponse::json($data);
    }

    public function getMerchantBeneficiaryFile()
    {
        $file = (new Merchant\Service)->getMerchantBeneficiaryFile();

        // We'll be outputting an excel file
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // It will be called file.xls
        header('Content-Disposition: attachment; filename="merchant_beneficiary_list.xlsx"');

        $file->download('xlsx');
        // $file->save('php://output');

        // return Response::download($file);
        // return ApiResponse::json($data);
    }
}
