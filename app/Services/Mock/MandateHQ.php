<?php

namespace RZP\Services\Mock;

class MandateHQ
{
    public function registerMandate($input)
    {
        return [
            'error' => [
                'success' => true,
                'error_code' => "",
                'error_message' => ""
            ],
            'redirect_url' => "https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage",
            'mandate_register_id' => "ratn_GX3VC146gmBVNe"
        ];
    }

    public function confirmMandate($mandateRegisterId)
    {
        return [
            'error' => [
                'success' => true,
                'error_code' => "",
                'error_message' => ""
            ],
            'mandateId' => 'ratn_PP3VC146gmBVGG'
        ];
    }
}
