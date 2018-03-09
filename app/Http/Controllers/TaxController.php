<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class TaxController extends Controller
{
    use Traits\HasCrudMethods;

    /**
     * Metadata API for dashboard: returns a list of
     * Indian tax slabs and GST tax IDs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMetaGstTaxes()
    {
        $data = $this->service()->getMetaGstTaxes();

        return ApiResponse::json($data);
    }

    /**
     * Gets map of states name and GSTIN.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMetaStates()
    {
        $data = $this->service()->getMetaStates();

        return ApiResponse::json($data);
    }
}
