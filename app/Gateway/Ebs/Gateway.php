<?php

namespace RZP\Gateway\Ebs;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Gateway\Ebs\Entity;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Ebs\ResponseConstants as Resp;
use RZP\Gateway\Ebs\RequestConstants as Req;
use RZP\Gateway\Ebs\CardType;
use RZP\Gateway\Ebs\Utility;

class Gateway extends Base\Gateway
{
    const HASH_ALGO                 = 'SHA512';
    const MERCHANT_ID               = 'merchant_id';
    const HASH_SECRET               = 'hash_secret';

    const NETBANKING_CHANNEL        = '0';
    const CARD_CHANNEL              = '2';

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
        Resp::EBS_PAYMENT_ID        => ENTITY::EBS_PAYMENT_ID,
        Resp::TRANSACTION_ID        => ENTITY::TRANSACTION_ID,
        Resp::PAYMENT_ID            => ENTITY::EBS_PAYMENT_ID,
        Resp::REFERENCE             => ENTITY::PAYMENT_ID,
        Resp::ERROR_CODE             => ENTITY::ERROR_CODE,
        Resp::ERROR                 => ENTITY::ERROR_DESCRIPTION,
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $attr = $this->getAuthorizeContent($content);

        $payment = $this->createGatewayPaymentEntity($attr, $input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function capture(array $input)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        assert($payment['received'], 1);
    }


    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $this->validateCallbackGetSecureHash($input['gateway'], $input['terminal']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = $this->getGatewayEntityDataFromResponse($input);

        $payment->fill($content);

        $payment->saveOrFail();

        $errorCode = $input['gateway'][Resp::RESPONSE_CODE];

        if ($errorCode !== Status::SUCCESS)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($errorCode),
                $errorCode,
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


        $this->traceGatewayApiRequest($request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [$response->body]);

        $utility = new Utility;

        $parsed_response = $utility->parseResponseXml($response->body);

        $attr = $this->getRefundContent($parsed_response, $input);

        $refund = $this->createGatewayPaymentEntity($attr, $input);

        if ($parsed_response[Resp::ERROR] !== false)
        {
            $errorCode = $parsed_response[Resp::ERROR_CODE];

            $desc = ResponseCode::$reasonCodes[$errorCode];


            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($errorCode),
                $errorCode,
                $desc);
        }
    }

    public function verify(array $input)
    {
        // TODO complete this
    }

    public function getSecureHash($content, $terminal)
    {
        $hashData = $this->getSecretKey($terminal);

        ksort($content);

        foreach ($content as $key => $value)
        {
            if (empty($value))
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

        $content[ENTITY::RECEIVED] = True;

        $content[ENTITY::TRANSACTION_ID] = $input['gateway'][Resp::TRANSACTION_ID];

        $content[ENTITY::REQUEST_ID] = $input['gateway'][Resp::REQUEST_ID];

        $content[ENTITY::STATUS] = Status::AUTHORIZED;

        if ($input['gateway'][Resp::RESPONSE_CODE] !== Status::SUCCESS)
        {
            $content[ENTITY::STATUS] = Status::AUTHORIZED_FAILED;
        }

        return $content;
    }

    protected function traceGatewayApiRequest($request)
    {
        unset ($request['content'][Req::API_SECRET_KEY]);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [$request]);
    }

    protected function getPaymentRefundRequestContent($payment, $input)
    {
        $refundAmount = (string) ($input['refund']['amount']/100);

        $content = array(
            Req::API_ACTION         => 'refund',
            Req::API_ACCOUNT_ID     => $this->getAccountId($input['terminal']),
            Req::API_SECRET_KEY     => $this->getSecretKey($input['terminal']),
            Req::API_AMOUNT         => $refundAmount,
            Req::API_PAYMENT_ID     => $payment['ebs_payment_id'],
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
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($input['payment']['id']);
        $payment->fill($attributes);
        $payment->setAction($this->action);
        $payment->saveOrFail();

        return $payment;
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
        $content = array(
            Req::ACCOUNT_ID    => $this->getAccountId($input['terminal']),
            Req::REFRENCE_NO   => $input['payment']['id'],
            Req::AMOUNT        => $input['payment']['amount']/100,
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
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Payment Method');
        }

        $content[Req::SECURE_HASH] = $this->getSecureHash($content, $input['terminal']);

        return $content;
    }

    protected function setContentForCard(&$content, $input)
    {
        $content[Req::CHANNEL] = self::CARD_CHANNEL;
        $content[Req::NAME_ON_CARD] = $input['card']['name'];
        $content[Req::CARD_NUMBER] = $input['card']['number'];
        $content[Req::CARD_EXPIRY] = $this->getExpiry($input);
        $content[Req::CARD_BRAND] = $this->getCardBrand($input);
        $content[Req::CARD_CVV] = $input['card']['cvv'];
    }

    protected function setContentForNetBanking(&$content, $input)
    {
        $content[Req::CHANNEL] = self::NETBANKING_CHANNEL;
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
        if ($this->mode === Mode::TEST)
        {
            $retVal = Req::CREDIT;
        }
        else if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $retVal = Req::NETBANKING;
        }
        else if ($input['payment']['method'] === Payment\Method::CARD)
        {
            if ($input['card']['type'] === Card\Type::DEBIT)
            {
                $retVal = Req::DEBIT;
            }
            else if ($input['card']['type'] === Card\Type::CREDIT)
            {
                $retVal = Req::CREDIT;
            }
        }

        if (empty($retVal))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Use Netbanking or Valid Credit/Debit Card');
        }

        return $retVal;
    }

    protected function getCardBrand($input)
    {
        if ($this->mode === Mode::TEST)
        {
            $retVal = Req::VISA;
        }
        else
        {
            switch ($input['card']['network'])
            {
                case Card\Network::VISA:
                    $retVal = CardType::VISA;

                case Card\Network::MC:
                    $retVal = CardType::MC;

                case Card\Network::MAES:
                    $retVal = CardType::MAES;

                case Card\Network::DICL:
                    $retVal = CardType::DICL;

                case Card\Network::AMEX:
                    $retVal = CardType::AMEX;

                case Card\Network::JCB:
                    $retVal = CardType::JCB;

                default:
                    throw new Exception\BadRequestValidationFailureException(
                        'Card Network not implemented');
            }
        }

        return $retVal;
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
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        // Remove secureHash Value to calculate Expected Hash Value
        unset($input[Resp::SECURE_HASH]);

        $expectedHash = $this->getSecureHash($input, $terminal);
        if ($hash !== $expectedHash)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }
    }

    protected function getAuthorizeContent($content)
    {
        $attr = array();

        $attr[Req::REFRENCE_NO] = $content[Req::REFRENCE_NO];
        $attr[Req::AMOUNT] = $content[Req::AMOUNT];
        $attr[ENTITY::STATUS] = Status::CREATED;

        return $attr;
    }

    protected function getRefundContent($response, $input)
    {
        $refundAmount = (string) ($input['refund']['amount']/100);

        $attr = $this->getMappedAttributes($response);

        $attr[ENTITY::REF_AMOUNT] = $refundAmount;

        $attr[ENTITY::REFUND_ID] = $input['refund']['id'];
        $attr[ENTITY::REFUND_REF_NO] = $input['payment']['id'];
        $attr[ENTITY::AMOUNT] = $refundAmount;

        $attr[ENTITY::RECEIVED] = True;
        $attr[ENTITY::STATUS] = Status::REFUNDED;
        $attr[ENTITY::PAYMENT_ID] = $input['payment']['id'];
        $attr[ENTITY::MODE] = strtoupper($this->mode);

        if ($attr[ENTITY::ERROR_CODE] !== 0)
        {
            $attr[ENTITY::STATUS] = Status::REFUND_FAILED;
        }

        return $attr;
    }
}
