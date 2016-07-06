<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Emi;

class EmiController extends BaseController
{
    public function fetchAvailableEmiPlans()
    {
        $data = (new Emi\Service)->all();

        return ApiResponse::json($data);
    }

    public function addEmiPlan()
    {
        $input = Input::all();

        $data = (new Emi\Service)->addEmiPlan($input);

        return ApiResponse::json($data);
    }

    public function fetchEmiPlanById($id)
    {
        $data = (new Emi\Service)->fetch($id);

        return ApiResponse::json($data);

    }

    public function deleteEmiPlan($id)
    {
        $data = (new Emi\Service)->deleteEmiPlan($id);

        return ApiResponse::json($data);
    }

    public function generateEmiExcel()
    {
        $input = Input::all();

        $emiExcel = (new Emi\Service)->getEmiFiles($input);

        return ApiResponse::json($emiExcel);
    }

}
