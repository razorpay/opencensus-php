<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Models\Admin;
use RZP\Models\Admin\Service as AdminService;

class PreferencesHelper extends P2pHelper
{
    public function getGatewayPreferences(string $gatewayId, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('turbo/preferences',[$gatewayId]);

        $default = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function createBankAccountForCustomerForPreferences(string $customer_id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);


        $request = $this->request('customers/%s/bank_account', [$customer_id] ,true, );

        $default = [
            "ifsc_code" => "ICIC0001207",
            'account_number' => '04030403040304',
            'beneficiary_name'=> 'RATN0000001',
            "beneficiary_address1"  => "address 1",
            "beneficiary_address2"  => "address 2",
            "beneficiary_address3"  => "address 3",
            "beneficiary_address4"  => "address 4",
            "beneficiary_email"     => "random@email.com",
            "beneficiary_mobile"    => "9988776655",
            "beneficiary_city"      =>"Kolkata",
            "beneficiary_state"     => "WB",
            "beneficiary_country"   => "IN",
            "beneficiary_pin"      =>"123456"
        ];

        $this->content($request, $default, $content);

        return $this->post($request,true);
    }
}
