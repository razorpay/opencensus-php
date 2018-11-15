<?php

namespace RZP\Gateway\Paysecure;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;
    use RequestHandlerTrait;

    protected $gateway = 'paysecure';

    public function __construct()
    {
        parent::__construct();

        $this->wsdlDetails =  [
            'header' => [
                'namespace' => 'https://paysecure/merchant.soap.header/',
                'key' => 'RequestorCredentials',
            ],
            'body' => [
                'namespace' => 'https://paysecure/merchant.soap/',
                'key' => 'CallPaySecure'
            ],
        ];

        $this->wsdlDetails['wsdl_file'] = dirname(__FILE__) . '/rupay.wsdl.test';
    }

    /**
     * @param array $input
     * @return array|void
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        // Todo: Don't trace card number and cvv
        $this->app['trace']->info(
            TraceCode::GATEWAY_REQUEST_INPUT_RECEIVED,
            $input
        );

        $checkBin2Response = $this->checkBin2();

        $this->handleFailure($checkBin2Response, 'checkbin2');

        if ($checkBin2Response[Fields::IMPLEMENTS_REDIRECT] === Constants::VALUE_TRUE)
        {
            $response = $this->initiate2();

            $content = $this->getGatewayPaymentAttributes($response);

            $this->createGatewayPaymentEntity($content);

            return $this->getRedirectRequest($response);
        }
        else
        {
            // $this->initiate();

            // $this->createGatewayPaymentEntity();
        }
    }

    public function callback(array $input)
    {
        parent::callback($input);

        // Check payment status
        if ($input['gateway'][Fields::ACCU_RESPONSE_CODE] !== Constants::STATUS_CALLBACK_SUCCESS)
        {
            $traceData = [
                'gateway'    => $this->gateway,
                'response'   => $input['gateway'],
                'payment_id' => $input['payment']['id'],
            ];

            $internalErrorCode = ErrorCodes::getErrorCodeMapped($input['gateway'][Fields::ACCU_RESPONSE_CODE]);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $input['gateway'][Fields::ERROR_CODE],
                $input['gateway'][Fields::ERROR_MESSAGE],
                $traceData
            );
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        // Validates the request by checking hashe
        $this->validateRequestId($gatewayPayment);

        $response = $this->authorizeTransaction($gatewayPayment);

        if ($response[Fields::STATUS] !== Constants::STATUS_SUCCESS)
        {
            $traceData = [
                'gateway'    => $this->gateway,
                'response'   => $input['gateway'],
                'payment_id' => $input['payment']['id'],
            ];

            // todo: map error code for authorize response
            $internalErrorCode = ErrorCodes::getErrorCodeMapped($response[Fields::ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $input['gateway'][Fields::ERROR_CODE],
                $input['gateway'][Fields::ERROR_MESSAGE],
                $traceData
            );
        }

        return $this->getCallbackResponseData($input);
    }

    // ------------ Auth request helpers -----------------
    protected function getGatewayPaymentAttributes($response, $flow = 'redirect')
    {
        $redirectUrl = $response[Fields::REDIRECT_URL];

        $parsed = parse_url($redirectUrl);

        parse_str($parsed['query'], $parsed);

        $hkey = $parsed[Fields::ACCU_HKEY];

        $content = [
            Entity::GATEWAY_TRANSACTION_ID => $response[Fields::TRAN_ID ],
            Entity::HKEY                   => $hkey,
            Entity::FLOW                   => $flow,
        ];

        return $content;
    }

    protected function getRedirectRequest($response)
    {
        $redirectUrl = $response[Fields::REDIRECT_URL];

        $parsed = parse_url($redirectUrl);

        parse_str($parsed['query'], $parsed);

        $hkey = $parsed[Fields::ACCU_HKEY];

        $cardholderId = $parsed[ Fields::ACCU_CARDHOLDER_ID ];
        $guid         = $parsed[ Fields::ACCU_GUID ];
        $redirectUrl  = strtok($redirectUrl, '?');
        $session      = $this->input['payment']['id'];

        $dataToHash = [
            $response[Fields::TRAN_ID],
            $cardholderId,
            $guid,
            $session,
        ];

        $hash = $this->generateHashOfData($dataToHash, $hkey);

        // todo: Check if hexadecimal format is required here
        $hash = base64_encode($hash);

        $requestContent = [
            Fields::ACCU_CARDHOLDER_ID => $cardholderId,
            Fields::ACCU_GUID          => $guid,
            Fields::ACCU_RETURN_URL    => $this->input['callbackUrl'],
            Fields::SESSION            => $session,
            Fields::ACCU_REQUEST_ID    => $hash,
        ];

        $redirectArray = [
            'url'     => $redirectUrl,
            'method'  => 'post',
            'content' => $requestContent,
        ];

        return $redirectArray;
    }
    // ------------ Auth request helpers end -----------------

    // ------------ Callback request helpers -----------------
    /**
     * @param Entity $gatewayPayment
     * @throws Exception\RuntimeException
     */
    protected function validateRequestId(Entity $gatewayPayment)
    {
        $dataToHash = [
            $gatewayPayment[Entity::GATEWAY_TRANSACTION_ID],
            $this->input['gateway'][Fields::ACCU_GUID],
            $this->input['payment']['id'],
            $this->input['gateway'][Fields::ACCU_RESPONSE_CODE],
        ];

        $hash = $this->generateHashOfData($dataToHash, $gatewayPayment[Entity::HKEY]);

        $this->compareHashes($this->input['gateway'][Fields::ACCU_REQUEST_ID], $hash);
    }
    // ------------ Callback request helpers end -------------

    // ------------ General helpers --------------------------

    protected function getSoapClientObject($request)
    {
        $soapClient = new \SoapClient($request['wsdl'], $request['options']);

        $headers = $this->getRequestHeaders();

        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function createGatewayPaymentEntity(array $content)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->fill($content);

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($this->action);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function generateHashOfData($dataToHash, $key)
    {
        $str = implode('&', $dataToHash);

        return hash_hmac(HashAlgo::SHA256, $str, $key);
    }

    /**
     * @param $response
     * @param $action
     * @throws Exception\GatewayErrorException
     */
    protected function handleFailure($response, $action)
    {
        if ($response[Fields::STATUS] === Constants::STATUS_FAILURE)
        {
            $errorCode = ErrorCodes::getErrorCodeMapped($response[Fields::ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[Fields::ERROR_CODE],
                $response[Fields::ERROR_MESSAGE],
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $this->input['payment']['id'],
                    'command'    => $action,
                ]
            );
        }
    }

    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }
}
