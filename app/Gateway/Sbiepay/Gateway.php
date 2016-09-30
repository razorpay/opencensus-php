<?php

namespace RZP\Gateway\Sbiepay;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\VerifyResult;
use RZP\Trace\TraceCode;
use RZP\Models\Card;
use RZP\Models\Payment;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use AuthorizeFailed;

    protected $gateway = 'sbiepay';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $method = $input['payment']['method'];

        $params = array(
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
            $params['MerchantId'] = $this->getTestMerchantId();
            $params['PostingAmount'] = '5.00';
        }

        list($paymentDetails, $paymode) = $this->getPaymentMethodRelatedAttributes($input);

        $params['Paymode'] = $paymode;

        $content = $this->getEncryptedDataForAuthorizeRequest($params, $paymentDetails);

        $content['merchIdVal'] = $params['MerchantId'];

        $payment = $this->createGatewayPaymentEntity(array_merge($params, ['method' => $method]));

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $encData = $input['gateway']['encData'];

        $decryptedContent = $this->decrypt($encData);

        $content = $this->getContent($decryptedContent);

        $payment = $this->repo->findByPaymentIdAndAction(
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

        $payment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $params = array(
            'AggregatorId'      => $payment['AggregatorId'],
            'MerchantId'        => $payment['MerchantId'],
            'RefundRequestId'   => $input['refund']['id'],
            'ATRN'              => $payment['SBIePayReferenceID'],
            'PostingAmount'     => number_format($input['refund']['amount']/100,2),
            'MerchantCurrency'  => $input['refund']['currency'],
            'MerchantOrderNo'   => $payment['MerchantOrderNo'],
            'RefundResponseURL' => 'http://www.example.com',
        );

        if ($this->mode === Mode::TEST)
        {
            $params['MerchantId'] = $this->getTestMerchantId();
        }

        $content = $this->encrypt(['EncryptRefundDetails' => $params]);

        $content['merchIdVal'] = $params['MerchantId'];

        $request = $this->getStandardRequestArray($content);

        $response = $this->sendGatewayRequest($request);

        $values = $this->getFormValues($response->body, $request['url']);
        $values = $this->decrypt($values['encRefundData']);
        $values = $this->getContent($values);

        $params['refund_id'] = $input['refund']['id'];
        $params['received'] = 1;
        $params['method'] = $payment['method'];
        $params['Status'] = $values['Status'];
        $params['SBIePayReferenceID'] = $values['SBIePayReferenceID'];

        $refund = $this->createGatewayPaymentEntity($params);

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

        $params = array(
            'Atrn'            => $input['SBIePayReferenceID'],
            'MerchantId'      => $input['MerchantId'],
            'MerchantOrderNo' => $input['MerchantOrderNo'],
            'ReturnURL'       => $this->getUrl($this->action),
        );

        if ($this->mode === Mode::TEST)
        {
            $params['MerchantId'] = $this->getTestMerchantId();
        }

        $content = $this->encrypt(['encryptQuery' => $params]);

        $content['merchIdVal'] = $params['MerchantId'];
        $content['aggIdVal'] = 'SBIEPAY';

        $request = $this->getStandardRequestArray($content);

        $this->response = $this->sendGatewayRequest($request);

        $values = $this->getFormValues($this->response->body, $request['url']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY, $content);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = $this->decrypt($values['encStatusData']);

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

    protected function getEncryptedDataForAuthorizeRequest($params, $paymentDetails)
    {
        $encryptData = array(
           'EncryptTrans'          => $params,
           'EncryptpaymentDetails' => $paymentDetails,
           'EncryptbillingDetails' => explode("|", "NA|NA|NA|NA|NA|NA|NA|NA|NA|NA|N"),
           'EncryptshippingDetais' => explode("|", "NA|NA|NA|NA|NA|NA|NA|NA|NA|NA|N"));

        return $this->encrypt($encryptData);
    }

    protected function postRequest($content)
    {
        $content = http_build_query($content);

        $request = $this->getStandardRequestArray($content, 'get');

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

    protected function encrypt($content)
    {
        return Security::encrypt($content, $this->getSecret());
    }

    protected function decrypt($str)
    {
        return Security::decrypt($str, $this->getSecret());
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
