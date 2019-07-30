<?php

namespace RZP\Services\Mock;

use RZP\Services\SmartRouting as BaseSmartRouting;

class SmartRouting extends BaseSmartRouting
{

    public function sendPaymentData($data)
    {
        return json_decode('[  
   {  
      "id":"1000SharpTrmnl",
      "merchant_id":"100000Razorpay",
      "gateway":"sharp",
      "card":true,
      "category":"",
      "currency":"INR",
      "network_category":"",
      "netbanking":false,
      "upi":false,
      "bank_transfer":false,
      "aeps":false,
      "emandate":false,
      "emi":false,
      "capability":0,
      "emi_duration":0,
      "emi_subvention":"",
      "shared":true,
      "international":false,
      "tpv":0,
      "gateway_merchant_id":"test_merchant_sharp",
      "gateway_merchant_id_2":"",
      "gateway_terminal_id":"abcde",
      "gateway_acquirer":"hdfc",
      "mc_mpan":"1234560000000000",
      "visa_mpan":"1234560000000001",
      "rupay_mpan":"1234560000000002",
      "network_mpan":"",
      "vpa":"random@razorpay",
      "type":[  
         "non_recurring"
      ],
      "enabled_types":null,
      "mode":3,
      "corporate":0,
      "expected":false,
      "enabled_banks":null,
      "card_less_emi":false,
      "direct":false,
      "applicable_token":false,
      "bank":{  
         "name":"",
         "emandate":false,
         "npci":false,
         "retail":false,
         "corp":false,
         "tpv":false,
         "emi":false
      },
      "network":{  
         "name":"",
         "is_headless":false,
         "baharat_qr":false,
         "recurring":false
      },
      "issuer":{  
         "name":"",
         "acquirer":""
      },
      "gateway_info":{  
         "name":"",
         "auth_type":"",
         "is_tpv":false,
         "supported_networks":null,
         "supported_net_banking":null,
         "issuers":null,
         "recurring":false,
         "upi":false,
         "mcc":false,
         "subscription":false,
         "card":false,
         "netbanking":false,
         "direct_netbanking":false,
         "only_auth_gateway":false
      }
   }
]',true);
    }

    public function createGatewayRule($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function updateGatewayRule($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function deleteGatewayRule($id, $group)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }
}
