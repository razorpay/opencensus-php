<?php

namespace RZP\Gateway\Wallet\Amazonpay\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Action;
use RZP\Gateway\Wallet\Amazonpay\ReasonCode;
use RZP\Gateway\Wallet\Amazonpay\RequestFields;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields;

/**
 * This class cannot be marked as final, as it needs to be mocked for test cases
 * Class Server
 * @package RZP\Gateway\Wallet\Amazonpay\Mock
 */
class Server extends Base\Mock\Server
{
    /**
     * @var Gateway
     */
    private $gatewayInstance;

    //------------ Public methods ----------------//

    public final function authorize($input)
    {
        parent::authorize($input);

        $this->request($input, $this->action);

        $this->validateAuthorizeInput($input);

        $request = $this->getGatewayInstance()->getAmazonPaySdk()->getDecryptedData($input);

        $this->validateActionInput($request, 'decryptedAuthSign');

        $response = $this->getAuthorizeResponse($request);

        $this->content($input, 'amazonpay_change_callback');

        $url = urldecode($input[RequestFields::REDIRECT_URL]) . '?' . http_build_query($response);

        return \Redirect::away($url);
    }

    public final function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        // PHP Parse URL converts `.` to `_` which results in signature mismatch
        // We need to validate the input first with `_` as laravel considers `.` as array notation
        $input[RequestFields::VERIFY_START_TIME] = $input['CreatedTimeRange_StartTime'];
        $input[RequestFields::VERIFY_END_TIME]   = $input['CreatedTimeRange_EndTime'];
        unset($input['CreatedTimeRange_StartTime'], $input['CreatedTimeRange_EndTime']);

        $this->setInput($input);

        $this->getGatewayInstance()->getAmazonPaySdk()->verifyMockGatewayS2sSignature($input);

        $xml = $this->getVerifyResponse($input);

        return $this->makeXmlResponse($xml);
    }

    public final function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input);

        $this->request($input, $this->action);

        $xml = $this->getRefundResponse($input);

        return $this->makeXmlResponse($xml);
    }

    public final function verifyRefund($input)
    {
        $this->setInput($input);

        $this->setAction(Action::VERIFY_REFUND);

        $this->validateActionInput($input);

        $xml = $this->getVerifyRefundResponse($input);

        return $this->makeXmlResponse($xml);
    }

    //------------ Protected methods --------------//

    protected final function getGatewayInstance($bankingType = null)
    {
        if ($this->gatewayInstance === null)
        {
            $this->gatewayInstance = parent::getGatewayInstance($bankingType);

            $this->gatewayInstance->setMock(true);
        }

        return $this->gatewayInstance;
    }

    //------------- Private methods --------------//

    private function makeXmlResponse(string $xml)
    {
        $response = parent::makeResponse($xml);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }

    private function getAuthorizeResponse(array $request)
    {
        $response = [
            ResponseFields::AMAZON_ORDER_ID  => 'S04-3441699-5326071',
            ResponseFields::DESCRIPTION      => 'Txn Success',
            ResponseFields::AMOUNT           => $request[RequestFields::TOTAL_AMOUNT],
            ResponseFields::CURRENCY_CODE    => 'INR',
            ResponseFields::REASON_CODE      => ReasonCode::SUCCESS,
            ResponseFields::SELLER_ORDER_ID  => $request[RequestFields::ORDER_ID],
            ResponseFields::STATUS           => 'SUCCESS',
            ResponseFields::TRANSACTION_DATE => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $this->content($response, $this->action);

        $response[ResponseFields::SIGNATURE] = $this->getGatewayInstance()->getAmazonPaySdk()
                                                                          ->getVerifySign($response);

        $this->content($response, $this->action . 'SignatureFailed');

        return $response;
    }

    private function getVerifyResponse(array $request)
    {
        $xml = file_get_contents(__DIR__ . '/Xml/verify_response.xml');

        $override = [
            '{{random_payment_id}}'     => $request[RequestFields::QUERY_ID],
            '{{amount}}'                => '500.00',
            '{{reason_code}}'           => 'UpfrontChargeSuccess',
        ];

        return $this->getOverriddenResponse($xml, $override);
    }

    private function getRefundResponse(array $request)
    {
        $xml = file_get_contents(__DIR__ . '/Xml/refund_response.xml');

        $override = [
            '{{reference_id}}'      => $request[ResponseFields::REFUND_REF_ID],
            '{{refund_amount}}'     => $request['RefundAmount_Amount'],
            '{{refund_state}}'      => 'Pending',
            '{{refund_fee_amount}}' => '0.00',
        ];

        return $this->getOverriddenResponse($xml, $override);
    }

    private function getVerifyRefundResponse(array $request)
    {
        $xml = file_get_contents(__DIR__ . '/Xml/refund_verify_response.xml');

        $override = [
            '{{amazon_refund_id}}'  => $request['AmazonRefundId'],
            '{{reference_id}}'      => 'NeedToBeOverridden',
            '{{refund_amount}}'     => '1.00',
            '{{refund_state}}'      => 'Completed',
            '{{refund_fee_amount}}' => '0.00',
        ];

        return $this->getOverriddenResponse($xml, $override);
    }

    private function getOverriddenResponse(string $xml, array $override)
    {
        $content = [
            'xml'   => $xml,
            'data'  => $override
        ];

        $this->content($content, $this->action);

        return strtr($content['xml'], $content['data']);
    }
}
