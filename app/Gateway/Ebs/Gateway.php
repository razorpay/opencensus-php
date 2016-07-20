<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;
use RZP\Constants\ModeEbs;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
/*
    use carbon\carbon;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Billdesk;
use Requests;
use RZP\Trace\Trace;
use Symfony\Component\DomCrawler\Crawler;
 */
class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    protected $gateway = 'ebs';
    protected $map = array(
        'amount' => 'TxnAmount',
        'PaymentID' =>'ebs_payment_id',
    );

    protected function validateCallbackgetSecureHash(array $input)
    {
        $hash = $input['SecureHash'];
        unset($input['SecureHash']);
        $expectedHash = $this->getSecureHash($input);
        if ($hash !== $expectedHash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed Hash Verification'
            );
        }

    }

    public function authorize(array $input)
    {
        parent::authorize($input);
        $content = $this->getAuthRequestContentArray($input);
        $payment = $this->createGatewayPaymentEntity($content);

        //$this->traceGatewayPaymentRequest($request, $input);
        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => $content
        );
        return $request;
    }

    public function capture(array $input)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE
        );

        assert($payment['received'], 1);
    }


    public function callback(array $input)
    {
        parent::callback($input);
        $this->validateCallbackgetSecureHash($input['gateway']);

        // Unset date because format of date returned is different than what we sent
        unset($input['gateway']['DateCreated']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']
        );
        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE
        );

        if ($input['gateway']['ResponseCode'] != '0')
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['AuthStatus'],
                ''
            );
        }

        $content = $this->getMappedAttributes($input['gateway']);

        $content['received'] = 1;
        $content['TransactionID'] = $input['gateway']['TransactionID'];
        $content['RequestID'] = $input['gateway']['RequestID'];

        $payment->fill($content);

        $payment->saveOrFail();
    }

    public function refund(array $input)
    {
    }

    public function verify(array $input)
    {
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $attributes['TxnAmount'] = $attributes['amount']*100;
        $payment->setPaymentId($attributes['reference_no']);
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
            'account_id' => '20640', // read from config
            'reference_no' => $input['payment']['id'],
            'amount' => $input['payment']['amount']/100,
            'return_url' => $input['callbackUrl'],
            'name' => 'gaurav',
            'address' => 'razorpay office',
            'city' => 'Bangalore',
            'state' => 'KAR',
            'country' => 'IND',
            'postal_code' => '560038',
            'phone' => '9876543210',
            'email' => 'gaurav.d@razorpay.com',
            'ship_name' => 'gaurav',
            'ship_address' => 'razorpay office',
            'ship_state' => 'KAR',
            'ship_city' => 'Bangalore',
            'ship_postal_code' => '560038',
            'ship_country' => 'IND',
            'ship_phone' => '9876543210',
            'description' => 'EBS paymnet',
            'currency' => 'INR',
            'mode' => strtoupper($this->mode),
            'payment_mode' => $this->getpaymentMode($input),
        );

        if ($input['payment']['method'] == "card")
        {
            $content['channel'] = '2';
            $content['name_on_card'] = $input['card']['name'];
            $content['card_number'] = $input['card']['number'];
            $content['card_expiry'] = $this->getExpiry($input);
            $content['card_brand'] = $this->getcardBrand($input);
            $content['card_cvv'] = $input['card']['cvv'];
        }

        if ($input['payment']['method'] == "netbanking")
        {
            $content['channel'] = '0';
            $bankId = BankCodes::$bankCodeMap[$input['payment']['bank']];
            $content['bank_code'] = $bankId;
        }
        $content['secure_hash'] = $this->getSecureHash($content);
        return $content;
    }
    protected function getExpiry($input)
    {
        $month = $input['card']['expiry_month'];
        $year = $input['card']['expiry_year'];
        $ex = mktime(0, 0, 0, $month, 1, $year);
        return date('my', $ex);
    }
    protected function getpaymentMode($input)
    {
        if ($input['payment']['method'] == "netbanking")
        {
            return '3';
        }
        $mode = $input['card']['type'];
        //TODO FIX me for all cases
        return '1';

    }
    protected function getcardBrand($input)
    {
        $brand = $input['card']['network_code'];
        //TODO FIX me
        return '1';
    }
    public function getSecureHash($content)
    {
        $secretKey = 'bd9c562902844435bcba33ee9528a4b3';
        // READ FROM config
        $hashData = $secretKey;
        ksort($content);

        foreach($content as $key => $value)
        {
            if (strlen($value) > 0)
            {
                $hashData .='|'.$value;
            }
        }
        if (strlen($hashData) > 0) {
            $hashValue = strtoupper(hash('SHA512',$hashData));
        }
        return $hashValue;
    }
}
