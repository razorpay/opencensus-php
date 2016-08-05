<?php

namespace RZP\Gateway\FirstData;

use Requests;
use RZP\Error;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Card;
use RZP\Trace\Trace;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Carbon\Carbon;

class Gateway extends Base\Gateway
{
    const TXNTYPE                   = 'txntype';
    const TIMEZONE                  = 'timezone';
    const TXNDATETIME               = 'txndatetime';
    const HASH_ALGORITHM            = 'hash_algorithm';
    const HASH                      = 'hash';
    const STORENAME                 = 'storename';
    const MODE                      = 'mode';
    const CHARGETOTAL               = 'chargetotal';
    const CURRENCY                  = 'currency';
    const OID                       = 'oid';
    const TDATE                     = 'tdate';
    const PAYMENT_METHOD            = 'paymentMethod';
    const CUSTOMERID                = 'customerid';
    const INVOICENUMBER             = 'invoicenumber';
    const CARD_FUNCTION             = 'cardFunction';
    const COMMENTS                  = 'comments';
    const RESPONSE_SUCCESS_URL      = 'responseSuccessURL';
    const RESPONSE_FAIL_URL         = 'responseFailURL';
    const DYNAMIC_MERCHANT_NAME     = 'dynamicMerchantName';
    const LANGUAGE                  = 'language';
    const HASH_EXTENDED             = 'hashExtended';
    const NUMBER_OF_INSTALLMENTS    = 'numberOfInstallments';
    const TRX_ORIGIN                = 'trxOrigin';
    const DCC_INQUIRY_ID            = 'dccInquiryId';

    const CARDNUMBER                = 'cardnumber';
    const EXPMONTH                  = 'expmonth';
    const EXPYEAR                   = 'expyear';
    const CVM                       = 'cvm';

    const APPROVAL_CODE             = 'approval_code';
    const RESPONSE_HASH             = 'response_hash';

    const TEST_STORE_ID             = 'test_store_id';
    const TEST_HASH_SECRET          = 'test_hash_secret';

    protected $gateway = \RZP\Constants\Entity::FIRST_DATA;

    public function authorize(array $input)
    {
        $input['card']['type']='credit';

        parent::authorize($input);

        $content = $this->getPreauthRequestContentArray($input);

        $payment = $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray($content, 'post');

        $this->traceGatewayPaymentRequest($request, $input);

        // $request = $this->makeRequestAndGetFormData($request);

        return $request;
    }

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $request = array(
            'url'       => $this->getUrl('processing'),
            'content'   => $content,
            'method'    => $method
        );

        return $request;
    }

    protected function getPreauthRequestContentArray($input)
    {
        $content = $this->getRequestContentArray($input);

        $content[self::TXNTYPE] = Codes::TXNTYPE_PREAUTH;

        $method = $input['card']['network_code'];

        $content[self::PAYMENT_METHOD] = Mapping::$paymentMethodCodes[$method];

        $this->setCardDetails($content, $input);

        return $content;
    }

    protected function setCardDetails(&$content, $input)
    {
        // $content[self::CARDNUMBER] = Card\Tokenex::getCardNumber($input['card']['vault_token']);
        $content[self::CARDNUMBER] = $input['card']['number'];

        $content[self::EXPMONTH] = $input['card']['expiry_month'];
        $content[self::EXPYEAR ] = $input['card']['expiry_year'];
        $content[self::CVM]      = $input['card']['cvv'];
    }

    protected function createGatewayPaymentEntity($content)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $payment->fill($content);
        $payment->setPaymentId($content[self::INVOICENUMBER]);
        $payment->setAction($this->action);
        $payment->saveOrFail();

        return $payment;
    }


    // This is a SHA hash of the following fields :
    // storename + txndatetime + chargetotal + currency + sharedsecret.
    protected function getRequestHash($txnDateTime, $chargeTotal, $currencyCode)
    {
        $storeId = $this->getStoreName();
        $sharedSecret = $this->getSecret();

        $stringToHash = $storeId . $txnDateTime . $chargeTotal . $currencyCode . $sharedSecret;
        $hash_algorithm = strtolower(Codes::FIRST_DATA_HASH_ALGORITHM);

        $hash = hash($hash_algorithm, bin2hex($stringToHash));

        return $hash;
    }

    // This is a SHA hash of the following fields :
    // sharedsecret + approvalcode + chargetotal + currency + txndatetime + storename.
    protected function getResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime)
    {
        $storeId = $this->getStoreName();
        $sharedSecret = $this->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;
        $hash_algorithm = strtolower(Codes::FIRST_DATA_HASH_ALGORITHM);

        $hash = hash($hash_algorithm, bin2hex($stringToHash));

        return $hash;
    }

    protected function getRequestContentArray($input)
    {
        $createdAt = $input['payment']['created_at'];
        $dateTime = Carbon::createFromTimestamp($createdAt, 'Asia/Kolkata');
        $txnDateTime = $dateTime->format(Codes::DATE_TIME_FORMAT);

        $chargeTotal = $input['payment']['amount'] / 100;
        $chargeTotal = number_format($chargeTotal,2,'.','');

        $currency = $input['payment']['currency'];
        $currencyCode = Mapping::$isoNumericCodes[$currency];

        $content = array(
            self::TIMEZONE                  => 'Asia/Kolkata',
            self::TXNDATETIME               => $txnDateTime,
            self::HASH_ALGORITHM            => Codes::FIRST_DATA_HASH_ALGORITHM,
            self::HASH                      => $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode),
            self::STORENAME                 => $this->getStoreName(),
            self::MODE                      => Codes::PAYMENT_MODE_PAYONLY,
            self::CHARGETOTAL               => $chargeTotal,
            self::CURRENCY                  => $currencyCode,

            self::OID                       => $input['payment']['id'],
            // self::CUSTOMERID                => $input['payment']['customer_id'],
            self::INVOICENUMBER             => $input['payment']['id'],

            self::CARD_FUNCTION             => $input['card']['type'],
            self::COMMENTS                  => '',

            self::RESPONSE_SUCCESS_URL      => $input['callbackUrl'],
            self::RESPONSE_FAIL_URL         => $input['callbackUrl'],

            self::DYNAMIC_MERCHANT_NAME     => 'Razorpay Payments',
            self::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE,
        );

        return $content;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway']['approval_code']) === false) or
            ($input['gateway']['approval_code'][0] !== 'Y'))
        {
            $this->trace->info(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, [$input['gateway']]);

            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
        }

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['gateway']['oid'], Base\Action::AUTHORIZE);

        $this->verifyHash($input['gateway'],$payment);

        // $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        ;
    }

    private function verifyHash($input)
    {
        $approvalCode = $input[self::APPROVAL_CODE];

        $txnDateTime = $input[self::TXNDATETIME];
        $chargeTotal = $input[self::CHARGETOTAL];
        $currencyCode = $input[self::CURRENCY];

        $expectedHash = $this->getResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime);

        if ($expectedHash != $input[self::RESPONSE_HASH])
        {
            $this->trace->error(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, array($input,$expectedHash));

            throw new Exception\BadRequestValidationFailureException('Failed response_hash verification');
        }
    }

    protected function getStoreName()
    {
        $terminal = $this->terminal;

        if ($this->mode ===Mode::TEST)
        {
            return $this->config[self::TEST_STORE_ID];
        }

        return $this->terminal['gateway_merchant_id'];
    }

    protected function getSharedSecret()
    {
        $terminal = $this->terminal;

        if ($this->mode ===Mode::TEST)
        {
            return $this->config[self::TEST_HASH_SECRET];
        }

        return $this->terminal['gateway_secure_secret'];
    }
}
