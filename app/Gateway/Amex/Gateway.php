<?php

namespace RZP\Gateway\Amex;

use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Amex;
use RZP\Gateway\AxisMigs;
use RZP\Models\Merchant;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends AxisMigs\Gateway
{
    protected $gateway = 'amex';

    protected $authorize = true;

    protected function addTestCardDetailsInTestMode(array & $content)
    {
        assertTrue ($this->mode === Mode::TEST);

        if ($content['vpc_CardNum'] === '4111111111111111')
        {
            return;
        }

        $content['vpc_Card'] = 'Amex';

        // 341111111111111, 345678000000007 are valid Amex card numbers

        // The following credentials are for Amex's real test gateway
        // Note: only these creds work on the Amex test gateway
        $content['vpc_CardNum'] = '341111111111111';
        $content['vpc_CardExp'] = '1705';
        $content['vpc_CardSecurityCode'] = '0773';
    }

    protected function getVpcCardValue($network)
    {
        return 'Amex';
    }

    protected function addSubMerchantDetails(array & $content, array $input)
    {
        $merchant = $input['merchant'];

        // scoping these extra request params to a specific merchant
        // to test on production Harshit's Merchant ID
        if ($merchant['id'] === Merchant\Account::TEST_ACCOUNT_2)
        {
            $ba = $merchant->bankAccount;

            $content['vpc_SubMerchant_ID'] = substr($merchant['id'], 0, 10);

            $tradingName = substr($merchant['name'], 0, 100);

            $registeredName = $tradingName;

            if (isset($merchant['billing_label']))
            {
                $registeredName = substr($merchant['billing_label'], 0, 100);
            }

            $content['vpc_SubMerchant_RegisteredName'] =  $registeredName;

            $content['vpc_SubMerchant_TradingName'] = $tradingName;

            $street = $ba['beneficiary_address1'];
            $street .= isset($ba['beneficiary_address2']) ? (', ' . $ba['beneficiary_address2']) : '';
            $street .= isset($ba['beneficiary_address3']) ? (', ' . $ba['beneficiary_address3']) : '';
            $street .= isset($ba['beneficiary_address4']) ? (', ' . $ba['beneficiary_address4']) : '';

            $content['vpc_SubMerchant_Street'] = $street;

            $content['vpc_SubMerchant_PostCode'] = $ba['beneficiary_pin'];

            $content['vpc_SubMerchant_City'] = $ba['beneficiary_city'];

            $content['vpc_SubMerchant_StateProvince'] = $ba['beneficiary_state'];

            $content['vpc_SubMerchant_Country'] = 'IND';

            $content['vpc_SubMerchant_Phone'] = substr($ba['beneficiary_mobile'], -10);

            $content['vpc_SubMerchant_Email'] = substr($merchant['email'], 0, 127);

            $content['vpc_SubMerchant_MerchantCategoryCode'] = $merchant['category'];
        }
    }

    protected function shouldRaiseErrorForInternationalMerchant(array $input) : bool
    {
        return false;
    }

    protected function verifyPaymentCallbackResponse($gatewayPayment, array $input)
    {
        $data = parent::verifyPaymentCallbackResponse($gatewayPayment, $input);

        $threeDSenrolled = $input['gateway']['vpc_3DSenrolled'] ?? null;

        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];

        $message = $input['gateway']['vpc_Message'] ?? '';

        $threeDSstatus = $input['gateway']['vpc_3DSstatus'] ?? null;

        $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_AMEX_3DSECURE_AUTH_FAILED;

        if ($threeDSenrolled !== 'Y')
        {
            $this->throwException($apiErrorCode, $txnResponseCode, $message, $threeDSstatus);
        }

        if ($threeDSstatus !== 'Y')
        {
            $this->throwException($apiErrorCode, $txnResponseCode, $message, $threeDSstatus);
        }

        return $data;
    }

    protected function verifyPayment($verify)
    {
        parent::verifyPayment($verify);

        $content = $verify->verifyResponseContent;

        $threeDSenrolled = $content['vpc_3DSenrolled'] ?? null;

        $threeDSstatus = $content['vpc_3DSstatus'] ?? null;

        if (($threeDSenrolled !== 'Y') and ($threeDSenrolled !== null))
        {
            $verify->gatewaySuccess = false;

            $verify->status = $verify->apiSuccess === true ? VerifyResult::STATUS_MISMATCH : VerifyResult::STATUS_MATCH;
        }

        if (($threeDSstatus !== 'Y') and ($threeDSenrolled !== null))
        {
            $verify->gatewaySuccess = false;

            $verify->status = $verify->apiSuccess === true ? VerifyResult::STATUS_MISMATCH : VerifyResult::STATUS_MATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        return $verify->status;
    }
}
