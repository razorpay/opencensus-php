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
use RZP\Constants\ModeEbs;
use RZP\Gateway\Ebs\Entity;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Ebs\ResponseConstants as RESP;
use RZP\Gateway\Ebs\RequestConstants as REQ;

class Gateway extends Base\Gateway
{
    const SUCCESS                   = '0';

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
        RESP::AMOUNT                => ENTITY::TXN_AMOUNT,
        RESP::EBS_PAYMENT_ID        => ENTITY::EBS_PAYMENT_ID,
        RESP::TRANSACTION_ID        => ENTITY::TRANSACTION_ID,
        RESP::PAYMENT_ID            => ENTITY::EBS_PAYMENT_ID,
        RESP::MODE                  => ENTITY::MODE,
        RESP::REFERENCE             => ENTITY::PAYMENT_ID,
        RESP::ERRORCODE             => ENTITY::ERROR_CODE,
        RESP::ERROR                 => ENTITY::ERROR_DESCRIPTION,
    );

    public function authorize(array $input)
    {
        parent::authorize($input);
        $content = $this->getAuthRequestContentArray($input);

        $attr = $this->getAuthorizeContent($content);

        $payment = $this->createGatewayPaymentEntity($attr);

        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => $content
        );

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

        $this->validateCallbackGetSecureHash($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);


        $content = $this->getMappedAttributes($input['gateway']);

        $content[ENTITY::RECEIVED] = 1;
        $content[ENTITY::TRANSACTION_ID] = $input['gateway'][RESP::TRANSACTION_ID];
        $content[ENTITY::REQUEST_ID] = $input['gateway'][RESP::REQUEST_ID];
        $content[ENTITY::STATUS] = Status::AUTHORIZED;

        if ($input['gateway'][RESP::RESPONSE_CODE] !== self::SUCCESS)
        {
            $content[ENTITY::STATUS] = Status::AUTHORIZED_FAILED;
        }

        $payment->fill($content);

        $payment->saveOrFail();

        if ($input['gateway'][RESP::RESPONSE_CODE] !== self::SUCCESS)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $input['gateway']['ResponseCode'],
                '');
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
                                $input['payment']['id'], Action::AUTHORIZE);

        $content = $this->getPaymentRefundRequestContent($payment, $input);

        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => $content);

        $response = $this->sendGatewayRequest($request);
        $resp = Utility::parseResponseXml($response->body);

        $attr = $this->getRefundContent($resp, $input);

        $refund = $this->createGatewayPaymentEntity($attr);


        if ($resp[RESP::ERROR] !== false)
        {
            try
            {
                $errorCode = $resp[RESP::ERRORCODE];

                $desc = ResponseCode::$reasonCodes[$errorCode];;

                $this->trace->error(
                    TraceCode::PAYMENT_REFUND_FAILURE,
                    [$response->body]);

                throw new Exception\GatewayErrorException(
                    ResponseCode::getMappedCode($errorCode),
                    $errorCode,
                    $desc);
            }
            catch (Exception\InvalidArgumentException $e)
            {
                $errorCode = $resp[RESP::ERRORCODE];

                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_REFUND_FAILED,
                    $errorCode,
                    ResponseCode::UNKNOWN_ERROR);
            }
        }
    }

    public function verify(array $input)
    {
    }

    public function getSecureHash($content)
    {
        $hashData = $this->config[self::HASH_SECRET];

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

    protected function getPaymentRefundRequestContent($payment, $input)
    {
        $refundAmount = (float) ($input['refund']['amount']);

        $refundAmount = (string) number_format($refundAmount/100, 2, '.', '');

        $content = array(
            REQ::REFUND_ACTION      => 'refund',
            REQ::REFUND_ACCOUNT_ID  => $this->config[self::MERCHANT_ID],
            REQ::REFUND_SECRET_KEY  => $this->config[self::HASH_SECRET],
            REQ::REFUND_AMOUNT      => $refundAmount,
            REQ::REFUND_PAYMENT_ID  => $payment['ebs_payment_id'],
        );
        return $content;
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $attributes[REQ::TXN_AMOUNT] = $attributes[REQ::AMOUNT]*100;
        $payment->setPaymentId($attributes[REQ::REFRENCE_NO]);
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
            REQ::ACCOUNT_ID    => $this->config[self::MERCHANT_ID],
            REQ::REFRENCE_NO   => $input['payment']['id'],
            REQ::AMOUNT        => $input['payment']['amount']/100,
            REQ::CALLBACK      => $input['callbackUrl'],
            REQ::NAME          => self::NAME,
            REQ::ADDRESS       => self::ADDRESS,
            REQ::CITY          => self::CITY,
            REQ::COUNTRY       => self::COUNTRY_CODE,
            REQ::POSTAL_CODE   => self::POSTAL_CODE,
            REQ::PHONE         => self::PHONE,
            REQ::EMAIL         => self::EMAIL,
            REQ::DESCRIPTION   => self::DESCRIPTION,
            REQ::CURRENCY      => self::CURRENCY,
            REQ::MODE          => strtoupper($this->mode),
            REQ::PAYMENT_MODE  => $this->getpaymentMode($input),
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

        $content[REQ::SECURE_HASH] = $this->getSecureHash($content);

        return $content;
    }

    protected function setContentForCard(&$content, $input)
    {
        $content[REQ::CHANNEL] = '2';
        $content[REQ::NAME_ON_CARD] = $input['card']['name'];
        $content[REQ::CARD_NUMBER] = $input['card']['number'];
        $content[REQ::CARD_EXPIRY] = $this->getExpiry($input);
        $content[REQ::CARD_BRAND] = $this->getcardBrand($input);
        $content[REQ::CARD_CVV] = $input['card']['cvv'];
    }

    protected function setContentForNetBanking(&$content, $input)
    {
        $content[REQ::CHANNEL] = '0';
        $bankId = BankCodes::$bankCodeMap[$input['payment']['bank']];
        $content[REQ::PAYMENT_OPTION] = $bankId;
    }

    protected function getExpiry($input)
    {
        $month = $input['card']['expiry_month'];
        $year = $input['card']['expiry_year'];
        return Carbon::createFromDate($year, $month)->format('my');
    }

    protected function getpaymentMode($input)
    {
        if ($this->mode === 'test')
        {
            $retVal = REQ::CREDIT;
        }
        else if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $retVal = REQ::NETBANKING;
        }
        else if ($input['payment']['method'] === Payment\Method::CARD)
        {
            if ($input['card']['type'] === Card\Type::DEBIT)
            {
                $retVal = REQ::DEBIT;
            }
            else if ($input['card']['type'] === Card\Type::CREDIT)
            {
                $retVal = REQ::CREDIT;
            }
        }

        if (empty($retVal))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Use Netbanking or Valid Credit/Debit Card');
        }

        return $retVal;
    }

    protected function getcardBrand($input)
    {
        if ($this->mode === 'test')
        {
            $retVal = REQ::VISA;
        }
        else if ($input['card']['network'] === Card\Network::VISA)
        {
            $retVal = REQ::VISA;
        }
        else if ($input['card']['network'] === Card\Network::MC)
        {
            $retVal = REQ::MC;
        }
        else if ($input['card']['network'] === Card\Network::MAES)
        {
            $retVal = REQ::MAES;
        }
        else if ($input['card']['network'] === Card\Network::DICL)
        {
            $retVal = REQ::DICL;
        }
        else if ($input['card']['network'] === Card\Network::AMEX)
        {
            $retVal = REQ::AMEX;
        }
        else if ($input['card']['network'] === Card\Network::JCB)
        {
            $retVal = REQ::JCB;
        }

        if (empty($retVal))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Card Network not implemented');
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

    protected function validateCallbackGetSecureHash(array $input)
    {
        $hash = $input[RESP::SECURE_HASH];
        if (empty($hash))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Hash');
        }

        // Remove secureHash Value to calculate Expected Hash Value
        unset($input[RESP::SECURE_HASH]);

        $expectedHash = $this->getSecureHash($input);
        if ($hash !== $expectedHash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed Hash Verification');
        }
    }

    protected function getAuthorizeContent($content)
    {
        $attr = $content;
        $attr[ENTITY::STATUS] = Status::CREATED;

        return $attr;
    }

    protected function getRefundContent($resp, $input)
    {
        $refundAmount = (float) ($input['refund']['amount']);
        $refundAmount = (string) number_format($refundAmount/100, 2, '.', '');

        $attr = $this->getMappedAttributes($resp);

        $attr[ENTITY::REF_AMOUNT] = $refundAmount;

        $attr[ENTITY::REFUND_ID] = $input['refund']['id'];
        $attr[ENTITY::REFUND_REF_NO] = $input['payment']['id'];
        $attr[ENTITY::AMOUNT] = $refundAmount;

        $attr[ENTITY::CURRENCY] = self::CURRENCY;
        $attr[ENTITY::RECEIVED] = 1;
        $attr[ENTITY::STATUS] = Status::REFUNDED;

        if ($attr[ENTITY::ERROR_CODE] !== 0)
        {
            $attr[ENTITY::TXN_AMOUNT] = $refundAmount;
            $attr[ENTITY::MODE] = strtoupper($this->mode);
            $attr[ENTITY::REFUND_PAYMENT_ID] = $input['payment']['id'];
            $attr[ENTITY::STATUS] = Status::REFUND_FAILED;
        }
        return $attr;
    }
}
