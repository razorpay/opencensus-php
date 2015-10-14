<?php

namespace Gateway\Sbiepay;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\AuthorizeFailed;
use Gateway\Base\VerifyResult;
use Models\Card;
use Models\Payment;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use AuthorizeFailed;

    protected $gateway = 'sbiepay';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $method = $input['payment']['method'];

        $requestParameter = array(
            'MerchantId'         => $input['terminal']['gateway_merchant_id'],
            'OperatingMode'      => 'DOM',
            'MerchantCountry'    => 'IN',
            'MerchantCurrency'   => 'INR',
            'PostingAmount'      => $input['payment']['amount'] / 100,
            'OtherDetails'       => 'NA',
            'SuccessURL'         => $input['callbackUrl'],
            'FailURL'            => $input['callbackUrl'],
            'AggregatorId'       => 'SBIEPAY',
            'MerchantOrderNo'    => $input['payment']['id'],
            'MerchantCustomerID' => $input['payment']['email'],
            'Paymode'            => 'NB',
            'Accesmedium'        => 'ONLINE',
            'TransactionSource'  => 'ONLINE'
        );

        if ($this->mode === Mode::TEST)
        {
            $requestParameter['MerchantId'] = $this->getTestMerchantId();
            $requestParameter['PostingAmount'] = '5.00';
        }

        list($paymentDetails, $paymode) = $this->getPaymentMethodRelatedAttributes($input);

        $requestParameter['Paymode'] = $paymode;

        $content = $this->getEncryptedDataForAuthorizeRequest($requestParameter, $paymentDetails);

        $content['merchIdVal'] = $requestParameter['MerchantId'];

        $payment = $this->createGatewayPaymentEntity(array_merge($requestParameter, ['method' => $method]));

        $request = array(
            'url'     => $this->getUrl('pay'),
            'content' => $content,
            'method'  => 'post');

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $encData = $input['gateway']['encData'];

        $decryptedContent = EncryptDecrypt::decryptData($encData, $this->getSecret());

        $content = $this->getContent($decryptedContent);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $content['MerchantOrderNo'], Action::AUTHORIZE);

        $content['received'] = 1;
        $payment->fill($content);
        $payment->saveOrFail();

        if ($content['Status'] === Status::FAILURE)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['Status'],
                '');
        }
    }


    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $requestParameter['AggregatorId'] = $payment['AggregatorId'];
        $requestParameter['MerchantId'] = $payment['MerchantId'];
        $requestParameter['RefundRequestId'] = $input['refund']['id'];
        $requestParameter['ATRN'] = $payment['SBIePayReferenceID'];
        $requestParameter['PostingAmount'] = number_format($input['refund']['amount']/100,2);
        $requestParameter['MerchantCurrency'] = $input['refund']['currency'];
        $requestParameter['MerchantOrderNo'] = $payment['MerchantOrderNo'];
        $requestParameter['RefundResponseURL'] = 'http://www.example.com';

        if ($this->mode === Mode::TEST)
        {
            $requestParameter['MerchantId'] = $this->getTestMerchantId();
        }

        $content = EncryptDecrypt::encryptData(array(
                                                   'EncryptRefundDetails' => $requestParameter,
                                               ));

        $content['merchIdVal'] = $requestParameter['MerchantId'];

        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);

        $response = $this->sendGatewayRequest($request);

        $crawler = new Crawler($response->body, 'http://www.example.com');
        $form = $crawler->filter('form')->form();
        $values = $form->getValues();
        $values = EncryptDecrypt::decryptData($values['encRefundData']);
        $values = $this->getContent($values);
        $requestParameter['refund_id'] = $input['refund']['id'];
        $requestParameter['received'] = 1;
        $requestParameter['method'] = $payment['method'];
        $requestParameter['Status'] = $values['Status'];
        $requestParameter['SBIePayReferenceID'] = $values['SBIePayReferenceID'];
        $refund = $this->createGatewayPaymentEntity($requestParameter);

        if ($values['Status'] !== Status::SUCCESS)
        {
            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                [$content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function authorizeFailed(array $input)
    {
        $e = null;

        try
        {
            $this->verify($input);
        } catch (Exception\PaymentVerificationException $e)
        {
            ;
        }

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not');
        }

        $verify = $e->getVerifyObject();

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true)
        )
        {
            $payment = $verify->payment;
            $payment->fill($verify->verifyResponseContent);
            $payment->saveOrFail();
        }
        else
        {
            throw new Exception\LogicException('Should not have reached here');
        }

        return true;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

        $status = VerifyResult::STATUS_MATCH;

        $content = $this->getContent($content);
        if ($content['Status'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;
            if (($payment['received'] === false) and
                (($payment['Status'] === null) or
                    ($payment['Status'] !== Status::SUCCESS))
            )
            {
                $verify->apiSuccess = false;
            }
            else
            {
                if ($payment['Status'] === Status::SUCCESS)
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->apiSuccess = true;
                }
            }
        }
        else
        {
            if ($content['Status'] === Status::SUCCESS)
            {
                $verify->gatewaySuccess = true;
                //Gateway success , api success
                if ($payment['Status'] === Status::SUCCESS)
                {
                    $verify->apiSuccess = true;
                }
                else
                {
                    if ($payment['Status'] !== Status::SUCCESS)
                    {
                        $verify->status = VerifyResult::STATUS_MISMATCH;
                        $verify->apiSuccess = false;
                    }
                }
            }
        }
        $verify->status = $status;
        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        if (($verify->match === true) and
            ($payment['received'] === false))
        {
            $payment->fill($content);
            $payment->saveOrFail();
        }

        return $status;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->payment;

        $requestParameter = array(
            'Atrn'            => $input['SBIePayReferenceID'],
            'MerchantId'      => $input['MerchantId'],
            'MerchantOrderNo' => $input['MerchantOrderNo'],
            'ReturnURL'       => $this->getUrl($this->action),
        );

        if ($this->mode === Mode::TEST)
        {
            $requestParameter['MerchantId'] = $this->getTestMerchantId();
        }

        $content = EncryptDecrypt::encryptData([
                                                   'encryptQuery' => $requestParameter
                                               ]);

        $content['merchIdVal'] = $requestParameter['MerchantId'];
        $content['aggIdVal'] = 'SBIEPAY';

        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);

        $this->response = $this->sendGatewayRequest($request);

        $data = $this->response->body;
        $crawler = new Crawler($data, 'http://www.example.com');
        $form = $crawler->filter('form')->form();
        $values = $form->getValues();

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = EncryptDecrypt::decryptData($values['encStatusData']);

        return $verify;
    }

    protected function getPaymentMethodRelatedAttributes($input)
    {
        $paymode = $paymentDetails = null;

        $method = $input['payment']['method'];

        if ($method === Payment\Method::NETBANKING)
        {
            $gatewayMapId = BankCodes::getBankCode($input['payment']['bank']);
            $paymentDetails = [$gatewayMapId, '', '', '', '', '', '', ''];
            $paymode = Paymode::NB;
        }

        if ($method === Payment\Method::CARD)
        {
            $gatewayMapId = $this->getGatewayMapIdValueForCard($input['card']);

            if ($input['card']['type'] === Card\Type::CREDIT)
            {
                $paymode = Paymode::CC;
            }
            else if ($input['card']['type'] === Card\Type::DEBIT)
            {
                $paymode = Paymode::DC;
            }

            $paymentDetails = array(
                $gatewayMapId,
                $input['card']['number'],
                $input['card']['cvv'],
                $input['card']['expiry_year'] . str_pad($input['card']['expiry_month'], 2, "0", STR_PAD_LEFT),
                'NA',
                'SBIN',
                strtoupper($input['card']['network']),
                $input['payment']['contact'],
                $input['card']['name']);
        }

        return [$paymentDetails, $paymode];
    }

    protected function getGatewayMapIdValueForCard($card)
    {
        $gatewayMapId = null;

        $network = $card['network'];

        if ($card['type'] === Card\Type::CREDIT)
        {
            $requestParameter['Paymode'] = Paymode::CC;

            if ($network === Card\Network::VISA)
            {
                $gatewayMapId = 2;
            }
            else if ($network === Card\Network::MC)
            {
                $gatewayMapId = 1;
            }
        }
        else
        {
            if ($card['type'] === Card\Type::DEBIT)
            {
                $requestParameter['Paymode'] = Paymode::DC;

                if ($network === Card\Network::VISA)
                {
                    $gatewayMapId = 5;
                }
                else if ($network === Card\Network::MC)
                {
                    $gatewayMapId = 4;
                }
                else if ($network === Card\Network::MAES)
                {
                    $gatewayMapId = 3;
                }
                else if ($network === Card\Network::RUPAY)
                {
                    $gatewayMapId = 55;
                }
            }
        }

        return $gatewayMapId;
    }

    protected function getEncryptedDataForAuthorizeRequest($requestParameter, $paymentDetails)
    {
        $encryptData = array(
           'EncryptTrans'          => $requestParameter,
           'EncryptpaymentDetails' => $paymentDetails,
           'EncryptbillingDetails' => explode("|", "NA|NA|NA|NA|NA|NA|NA|NA|NA|NA|N"),
           'EncryptshippingDetais' => explode("|", "NA|NA|NA|NA|NA|NA|NA|NA|NA|NA|N"));

        return EncryptDecrypt::encryptData($encryptData, $this->getSecret());
    }

    protected function postRequest($content)
    {
        $content = http_build_query($content);
        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);
        $response = $this->sendGatewayRequest($request);
    }

    protected function getContent($msg)
    {
        $fields = $this->getFieldsForAction($this->action);

        $content = explode('|', $msg);

        // @todo: explain this
        if ((count($content) === 23) and
            (($content[count($content) - 1] === '') or
             ($content[count($content) - 1] === null)))
        {
            unset($content[count($content) - 1]);
        }

        $content = array_combine($fields, $content);

        return $content;
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['MerchantOrderNo']);

        $payment->fill($attributes);
        $payment->setAction($this->action);
        $payment->saveOrFail();

        return $payment;
    }


    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected function getTestMerchantId()
    {
        return '1000109';
    }

}