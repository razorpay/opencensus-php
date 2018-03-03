<?php

namespace RZP\Gateway\Wallet\Amazonpay\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Wallet\Amazonpay\Action;
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

        $url = urldecode($input[RequestFields::REDIRECT_URL]) . '?' . http_build_query($response);

        return \Redirect::away($url);
    }

    public final function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, $this->action);

        $this->getGatewayInstance()->getAmazonPaySdk()->verifyMockGatewayS2sSignature($input);

        $xml = $this->getVerifyResponse($input);

        return $this->makeXmlResponse($xml);
    }

    public final function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, $this->action);

        $xml = $this->getRefundResponse($input);

        return $this->makeXmlResponse($xml);
    }

    public final function verifyRefund($input)
    {
        $this->setInput($input);
        $this->setAction(Action::VERIFY_REFUND);

        $this->validateActionInput($input, $this->action);

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

        $xml = $this->modifyOrderReference($xml, $request);

        $this->content($xml, $this->action);

        return $xml;
    }

    private function modifyOrderReference(string& $xml, array $request)
    {
        $paymentId = $request[RequestFields::QUERY_ID];

        $payment = $this->repo->payment->findByPublicId('pay_' . $paymentId);

        $paymentAmount = (string) ($payment->getAmount() / 100);

        $find = ['random_payment_id', '1.00'];

        $replace = [$paymentId, $paymentAmount];

        return str_replace($find, $replace, $xml);
    }

    private function getRefundResponse(array $request)
    {
        $xml = file_get_contents(__DIR__ . '/Xml/refund_response.xml');

        $xml = $this->modifyRefundReference($xml, $request);

        $this->content($xml, $this->action);

        return $xml;
    }

    private function modifyRefundReference(string& $xml, array $request)
    {
        $refundId = $request[RequestFields::REFUND_REF_ID];

        $refundAmount = $request['RefundAmount_Amount'];

        // TODO: Check if fee_refunded is the same as refund_amount
        $find = ['random_reference_id', 'refund_amount'];

        $replace = [$refundId, $refundAmount];

        return str_replace($find, $replace, $xml);
    }

    private function getVerifyRefundResponse(array $request)
    {
        $xml = file_get_contents(__DIR__ . '/Xml/refund_verify_response.xml');

        $find = ['amazon_refund_id'];

        $replace = [$request['AmazonRefundId']];

        $xml = str_replace($find, $replace, $xml);

        $this->content($xml, $this->action);

        return $xml;
    }
}
