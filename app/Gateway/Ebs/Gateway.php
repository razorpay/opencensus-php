<?php

namespace RZP\Gateway\Ebs;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Ebs\ResponseConstants as Resp;
use RZP\Gateway\Ebs\RequestConstants as Req;

class Gateway extends Base\Gateway
{
    const HASH_ALGO                 = 'SHA512';
    const MERCHANT_ID               = 'merchant_id';
    const HASH_SECRET               = 'hash_secret';

    const API                       = 'api';
    const NAME                      = 'Razorpay';
    const CITY                      = 'Bangalore';
    const EMAIL                     = 'helpdesk@razorpay.com';
    const PHONE                     = '9876543210';
    const ADDRESS                   = 'Razorpay office';
    const CURRENCY                  = 'INR';
    const POSTAL_CODE               = '560001';
    const DESCRIPTION               = 'razorpay ebs desc';
    const COUNTRY_CODE              = 'IND';

    protected $gateway = Constants\Table::EBS;

    protected $map = array(

        Resp::PAYMENT_ID            => Entity::REFERENCE_ID,
        Resp::MERCHANT_REF_NO       => Entity::PAYMENT_ID,
        Resp::IS_FLAGGED            => Entity::IS_FLAGGED,
        Resp::TRANSACTION_ID        => Entity::TRANSACTION_ID,
        Resp::REQUEST_ID            => Entity::REQUEST_ID,
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $attributes = $this->getAuthorizeContent($content);

        $payment = $this->createGatewayPaymentEntity($attributes, $input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        assert($gatewayPayment[Entity::STATUS] === Status::AUTHORIZED);
    }


    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            ['gateway' => $input['gateway']]);

        $this->validateCallbackGetSecureHash($input['gateway'], $input['terminal']);

        $gatewayPayment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attributes = $this->getGatewayEntityDataFromResponse($input);

        $gatewayPayment->fill($attributes);
        $gatewayPayment->saveOrFail();
        $responseCode = $input['gateway'][Resp::RESPONSE_CODE];

        if ($responseCode !== Status::SUCCESS)
        {
            //
            // Payment fails, throw exception
            //
            $desc = ResponseCode::$reasonCodes[$responseCode];

            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($responseCode),
                $responseCode,
                $desc);
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
                                $input['payment']['id'], Action::AUTHORIZE);

        $content = $this->getPaymentRefundRequestContent($payment, $input);

        $request = $this->getStandardRequestArray($content);

        $traceCode = TraceCode::GATEWAY_REFUND_REQUEST;

        $this->traceGatewayApiRequest($request, $traceCode);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [$response->body]);

        $parsedResponse = $this->parseResponseXml($response->body);

        $attributes = $this->getRefundContent($parsedResponse, $input);

        $refund = $this->createGatewayPaymentEntity($attributes, $input);

        if ((isset($parsedResponse[Resp::RESPONSE]) === false) or
            ($parsedResponse[Resp::RESPONSE] !== Status::API_SUCCESS))
        {
            $responseCode = $parsedResponse[Resp::ERROR_CODE];

            $desc = ResponseCode::$reasonCodes[$responseCode];

            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($responseCode),
                $responseCode,
                $desc);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if (isset($content[Resp::ERROR_CODE]) and
            $content[Resp::ERROR_CODE] !== 0)
        {
            $verify->apiSuccess = false;
        }
        else
        {
            $verify->gatewaySuccess = true;

            if (($input['payment']['status'] !== 'created') and
                ($input['payment']['status'] !== 'failed'))
            {
                $verify->apiSuccess = true;
            }
            else
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;
        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    protected function saveVerifyContentIfNeeded($payment, $response)
    {
        $attributes = $this->getVerifyContents($payment, $response);

        if ($payment === null)
        {
            $payment = $this->createGatewayPaymentEntity($attributes);
        }
        else if ($payment['received'] === false)
        {
            $payment->fill($attributes);
            $payment->saveOrFail();
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    protected function getVerifyContents($payment, $content)
    {
        $isFlagged = (strtolower($content[Resp::API_IS_FLAGGED]) === 'yes') ? true : false;

        $content = array(
            Entity::RECEIVED            => true,
            Entity::STATUS              => Status::SUCCESS,
            Entity::AMOUNT              => $this->input['payment']['amount'],
            Entity::IS_FLAGGED          => $isFlagged,
            Entity::TRANSACTION_ID      => $content[Resp::API_TRANSACTION_ID],
            Entity::REFERENCE_ID        => $content[Resp::API_REFERENCE_ID],
        );

        if (isset($payment['amount']) === false)
        {
            $content['amount'] = $this->input['payment']['amount'];
        }

        return $content;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = $this->getPaymentVerifyRequestContent($input, $payment);

        $request = $this->getStandardRequestArray($content);

        $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST;

        $this->traceGatewayApiRequest($request, $traceCode);

        $response = $this->sendGatewayRequest($request);

        $parsedResponse = $this->parseResponseXml($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [$response->body]);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $parsedResponse;

        return $parsedResponse;
    }

    public function getSecureHash($content, $terminal)
    {
        // Secret is the first key of the string
        $hashData = $this->getSecretKey($terminal);

        ksort($content);

        foreach ($content as $key => $value)
        {
            if (strlen($value) > 0)
            {
                $hashData .= '|' . $value;
            }
        }

        $hashValue = strtoupper(hash(self::HASH_ALGO, $hashData));

        return $hashValue;
    }

    protected function getGatewayEntityDataFromResponse($input)
    {
        $content = $this->getMappedAttributes($input['gateway']);

        $content[Entity::IS_FLAGGED] = (strtolower($content[Entity::IS_FLAGGED]) === 'yes') ? true : false;

        $content[Entity::RECEIVED] = true;

        $content[Entity::REQUEST_ID] = $input['gateway'][Resp::REQUEST_ID];

        $content[Entity::STATUS] = Status::AUTHORIZED;

        if ($input['gateway'][Resp::RESPONSE_CODE] !== Status::SUCCESS)
        {
            $content[Entity::STATUS] = Status::AUTHORIZE_FAILED;
        }

        return $content;
    }

    protected function traceGatewayApiRequest($request, $traceCode)
    {
        unset($request['content'][Req::API_SECRET_KEY]);

        $this->trace->info(
            $traceCode,
            $request);
    }

    protected function getPaymentVerifyRequestContent($input, $payment)
    {
        $content = array(
            Req::API_ACTION         => 'status',
            Req::API_ACCOUNT_ID     => $this->getAccountId($input['terminal']),
            Req::API_SECRET_KEY     => $this->getSecretKey($input['terminal']),
            Req::API_PAYMENT_ID     => $payment[Entity::REFERENCE_ID],
            req::API_TRANSACTION_ID => $payment[Entity::TRANSACTION_ID],
        );

        return $content;
    }

    protected function getPaymentRefundRequestContent($payment, $input)
    {
        $refundAmount = $input['refund']['amount']/100;

        $content = array(
            Req::API_ACTION         => 'refund',
            Req::API_ACCOUNT_ID     => $this->getAccountId($input['terminal']),
            Req::API_SECRET_KEY     => $this->getSecretKey($input['terminal']),
            Req::API_AMOUNT         => $refundAmount,
            Req::API_PAYMENT_ID     => $payment[Entity::REFERENCE_ID],
        );

        return $content;
    }

    protected function getAccountId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::MERCHANT_ID];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getSecretKey($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::HASH_SECRET];
        }

        return $terminal['gateway_secure_secret'];
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function getAuthRequestContentArray($input)
    {
        $amount = $input['payment']['amount']/100;

        $content = array(
            Req::ACCOUNT_ID    => $this->getAccountId($input['terminal']),
            Req::REFRENCE_NO   => $input['payment']['id'],
            Req::AMOUNT        => $amount,
            Req::CALLBACK      => $input['callbackUrl'],
            Req::NAME          => self::NAME,
            Req::ADDRESS       => self::ADDRESS,
            Req::CITY          => self::CITY,
            Req::COUNTRY       => self::COUNTRY_CODE,
            Req::POSTAL_CODE   => self::POSTAL_CODE,
            Req::PHONE         => self::PHONE,
            Req::EMAIL         => self::EMAIL,
            Req::DESCRIPTION   => self::DESCRIPTION,
            Req::CURRENCY      => self::CURRENCY,
            Req::MODE          => strtoupper($this->mode),
            Req::PAYMENT_MODE  => $this->getPaymentMode($input),
        );

        if ($input['payment']['method'] === Payment\Method::CARD)
        {
            $this->setContentForCard($content, $input);
        }
        else if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $this->setContentForNetBanking($content, $input);
        }
        else
        {
            throw new Exception\LogicException(
                'Invalid Payment Method');
        }

        $content[Req::SECURE_HASH] = $this->getSecureHash($content, $input['terminal']);

        return $content;
    }

    protected function setContentForCard(&$content, $input)
    {
        $content[Req::CHANNEL]          = Channel::CARD;
        $content[Req::NAME_ON_CARD]     = $input['card']['name'];
        $content[Req::CARD_NUMBER]      = $input['card']['number'];
        $content[Req::CARD_EXPIRY]      = $this->getExpiry($input);
        $content[Req::CARD_NETWORK]     = $this->getCardNetwork($input);
        $content[Req::CARD_CVV]         = $input['card']['cvv'];
    }

    protected function setContentForNetBanking(&$content, $input)
    {
        $content[Req::CHANNEL] = Channel::NETBANKING;
        $bankId = BankCodes::$bankCodeMap[$input['payment']['bank']];
        $content[Req::PAYMENT_OPTION] = $bankId;
    }

    protected function getExpiry($input)
    {
        $month = $input['card']['expiry_month'];
        $year = $input['card']['expiry_year'];

        return Carbon::createFromDate($year, $month)->format('my');
    }

    protected function getPaymentMode($input)
    {
        if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $paymentMode = PaymentMode::NETBANKING;
        }
        else if ($input['payment']['method'] === Payment\Method::CARD)
        {
            if ($input['card']['type'] === Card\Type::DEBIT)
            {
                $paymentMode = PaymentMode::DEBIT;
            }
            else if ($input['card']['type'] === Card\Type::CREDIT)
            {
                $paymentMode = PaymentMode::CREDIT;
            }
        }

        if (empty($paymentMode) === true)
        {
            throw new Exception\LogicException(
                'Use Netbanking or Valid Credit/Debit Card');
        }

        return $paymentMode;
    }

    protected function getCardNetwork($input)
    {
        switch ($input['card']['network'])
        {
            case Card\Network::VISA:
                $cardNetwork = CardNetwork::VISA;
                break;

            case Card\Network::MC:
                $cardNetwork = CardNetwork::MC;
                break;

            case Card\Network::MAES:
                $cardNetwork = CardNetwork::MAES;
                break;

            case Card\Network::DICL:
                $cardNetwork = CardNetwork::DICL;
                break;

            case Card\Network::AMEX:
                $cardNetwork = CardNetwork::AMEX;
                break;

            case Card\Network::JCB:
                $cardNetwork = CardNetwork::JCB;
                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Card Network not supported');
        }

        return $cardNetwork;
    }

    protected function getUrlDomain()
    {
        $apiDomainActionList = array(
            Action::CAPTURE,
            Action::REFUND);

        if (in_array($this->action, $apiDomainActionList))
        {
            $this->domainType = self::API;
        }

        return parent::getUrlDomain();
    }

    protected function validateCallbackGetSecureHash(array $input, $terminal)
    {
        $hash = $input[Resp::SECURE_HASH];

        if (empty($hash))
        {
            throw new Exception\LogicException(
                'Checksum verification failed');
        }

        // Remove secureHash Value to calculate Expected Hash Value
        unset($input[Resp::SECURE_HASH]);

        $expectedHash = $this->getSecureHash($input, $terminal);

        if ($hash !== $expectedHash)
        {
            throw new Exception\LogicException(
                'Checksum verification failed');
        }
    }

    protected function getAuthorizeContent($content)
    {
        $attributes = array();
        $attributes[Entity::AMOUNT]     = $content[Req::AMOUNT];
        $attributes[Entity::PAYMENT_ID] = $content[Req::REFRENCE_NO];
        $attributes[Entity::AMOUNT]     = $content[Req::AMOUNT];
        $attributes[Entity::STATUS]     = Status::CREATED;

        return $attributes;
    }

    protected function getRefundContent($response, $input)
    {
        $refundAmount = $input['refund']['amount']/100;

        $attributes = $this->getMappedAttributes($response);
        if (isset($response[Entity::IS_FLAGGED]))
        {
            $attributes[Entity::IS_FLAGGED] = (strtolower($response[Entity::IS_FLAGGED]) === 'yes') ? true : false;
        }
        $attributes[Entity::REFUND_ID] = $input['refund']['id'];

        $attributes[Entity::AMOUNT] = $refundAmount;

        $attributes[Entity::RECEIVED] = true;

        if (isset($response[Resp::ERROR_CODE]))
        {
            $attributes[Entity::ERROR_CODE] = $response[Resp::ERROR_CODE];
            $attributes[Entity::ERROR_DESCRIPTION] = $response[Resp::ERROR];
            $attributes[Entity::STATUS] = Status::REFUND_FAILED;
        }
        else
        {
            $attributes[Entity::STATUS] = Status::REFUNDED;
        }

        return $attributes;
    }

    protected function parseResponseXml($response)
    {
        $arrayResponse = (array) simplexml_load_string($response);

        return $arrayResponse['@attributes'];
    }
}
