<?php

namespace RZP\Services\Mock;

use RZP\Models\Terminal;
use RZP\Services\TerminalsService as BaseTerminalsService;

class TerminalsService extends BaseTerminalsService
{

    public function initiateOnboarding(string $merchantId, string $gateway, $identifiers = null, $features = null, array $currency = [], array $otherInputs = []): array
    {
        $terminals = $this->getTerminalsByMerchantIdAndGateway($merchantId, $gateway); // reusing the same terminal for all gateway onboarding tests

        $response = [
            'data' => $terminals[0]
        ];

        switch($gateway){
            case 'paysecure':
                $response['data']['gateway_acquirer'] = 'axis';
                break;
            case 'fulcrum':
                $response['data']['gateway_acquirer'] = 'ratn';
                break;
            case 'icici':
                $response['data']['gateway_acquirer'] = 'icici';
                break;
            case 'upi_juspay':
                $response['terminal'] = $terminals[0];
                break;
            case 'paytm':
                // To test that correct params are being passed to TS
                assert($otherInputs['secrets']['gateway_secure_secret'] === "merchant_provided_paytm_sec");
                assert($identifiers['gateway_merchant_id'] === "merchant_provided_paytm_key");
                assert($identifiers['gateway_terminal_id'] === "industry_type_id");
                break;
            case 'wallet_paypal':
                $response = [
                    "links" => "https://www.sandbox.paypal.com/IN/merchantsignup/partner/onboardingentry?token=MWRiYWM1NDQtZWJlZC00M2VjLTlkMGMtZmM2MjRmYzc0N2M4ZW5NUGdxS2FUb0ozcTRRYmtSUkd5bXNtYnJiOUs0Y2ZYQU9JZURVL29SWT12MQ==&context_token=4909428984085513216"
                ];
        }

        return $response;
    }

    public function getTerminalsByMerchantIdAndGateway(string $merchantId, string $gateway)
    {
        $response = [
                [
                   'id' =>  "ETbhgqkBRIiAkt",
                   'merchant_id' =>  $merchantId,
                   'org_id' =>  "100000razorpay",
                   'procurer' =>   "Razorpay",
                   'category' =>   "",
                   'network_category' =>   "",
                   'enabled_banks' => NULL,
                   'account_number' =>   "",
                   'ifsc_code' =>   "",
                   'virtual_upi_root' =>   "",
                   'virtual_upi_merchant_prefix' =>   "",
                   'virtual_upi_handle' =>   "",
                   'notes' => NULL,
                   'enabled' =>  FALSE,
                   'status' =>   "requested",
                   'gateway' =>  "wallet_paypal",
                   'gateway_merchant_id' =>   "",
                   'gateway_merchant_id2' =>   "",
                   'gateway_terminal_id' =>   "",
                   'gateway_vpa_whitelisted' => "Y",
                   'gateway_terminal_password' =>   "",
                   'gateway_terminal_password2' =>   "",
                   'gateway_secure_secret' =>   "",
                   'gateway_secure_secret2' =>   "",
                   'gateway_client_secret' =>   "",
                   'gateway_recon_password' =>   "",
                   'gateway_access_code' =>   "",
                   'gateway_acquirer' =>   "",
                   'mc_mpan' =>   "",
                   'visa_mpan' =>   "",
                   'rupay_mpan' =>   "",
                   'vpa' =>   "",
                   'card' =>  FALSE,
                   'netbanking' =>  FALSE,
                   'upi' =>  FALSE,
                   'omnichannel' =>  FALSE,
                   'bank_transfer' =>  FALSE,
                   'emandate' =>  FALSE,
                   'nach' =>  FALSE,
                   'aeps' =>  FALSE,
                   'emi' =>  FALSE,
                   'cardless_emi' =>  FALSE,
                   'paylater' =>  FALSE,
                   'emi_duration' =>  0,
                   'emi_subvention' =>   "",
                   'capability' =>  0,
                   'international' =>  FALSE,
                   'tpv' =>  0,
                   'type' =>  [
                        "direct_settlement_with_refund",
                   ],
                   'mode' =>  0,
                   'corporate' =>  0,
                   'expected' =>  FALSE,
                   'currency' =>  [
                         "USD",
                         "EUR",
                         "AUD",
                   ],
                   'created_at' =>  0,
                   'updated_at' =>  0,
                   'deleted_at' =>  0,
                   'entity' =>   "terminal",
                   'sub_merchants' => NULL,
                   'mpan' =>  [
                       'mc_mpan' =>   "",
                       'rupay_mpan' =>   "",
                       'visa_mpan' =>   "",
                   ]
               ]
        ];

        return $response;
    }

    public function proxyTerminalService($input, $method, $path, $options = [], $headers = []): array
    {
        if ($path === 'v2/terminal_test_otp/sms_sync')
        {
            return [
                'success' => true,
            ];
        }
        if ($path === 'v2/terminal_test_otp/ifttt')
        {
            return [
                'success' => true,
            ];
        }
        if ($path === 'v1/admin/terminals_with_secrets')
        {
            if (empty($input["terminal_ids"]) === false)
            {
                $apiTerminals = (new Terminal\Repository)->mockGetByTerminalIds($input["terminal_ids"]);

                return $apiTerminals->toArray();
            }
        }

        return [];
    }

    public function consumeInstrumentRulesEvaluationEvent($input): array
    {
        return [];
    }
}
