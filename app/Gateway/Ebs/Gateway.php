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
        'TransactionID' => 'TxnReferenceNo',
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
            'url' => 'https://secure.ebs.in/pg/ma/payment/request',
            'method' => 'post',
            'content' => $content
        );
        return $request;
    }

    public function capture(array $input)
    {
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
        $content = array();
        $content['BankPaymentID'] = 'hi';//$input['gateway']['TransactionID'];
//        $content['BankPaymentID'] = $input['gateway']['PaymentID'];
        $content['received'] = 1;
        $payment->fill($content);
        //dd($payment->saveOrFail());

        //dd($content);
        return;
        dd($input['gateway']);
        dd($payment);

        //TODO update DB and enjoy life
        $attrs = $this->getMappedAttributes($input['gateway']);


        $bankRefNo = $input['gateway']['BankRefNo'];
        $message = $input['gateway']['Message'];

        $attrs = $this->getMappedAttributes($input['gateway']);
        $attrs['received'] = true;

        $payment->fill($attrs);
        $payment->saveOrFail();

        if (($bankRefNo === '') or
            ($message !== ''))
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
                    '',
                    $message);
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);
        dd("here");
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                                $input['payment']['id'], Action::AUTHORIZE);
        dd($payment);
        $content = $this->getPaymentRefundRequestContent($payment, $input);

        $content = $this->postRequest($content);

        $content['refund_id'] = $input['refund']['id'];
        $content['CurrencyType'] = 'INR';
        $content['received'] = 1;
        $refund = $this->createGatewayPaymentEntity($content);

        if ($content['ProcessStatus'] !== 'Y')
        {
            //
            // For very very few transactions, the payment status on billdesk changes
            // after 1 whole day. These are automatically refunded by billdesk.
            // So, the AuthStatus changes to 0300 but RefundStatus also changes to 0699.
            // In that case, we need to let the refund go ahead.

            $refundAmount = (int) ($payment['RefAmount'] * 100);

            if (($content['ErrorCode'] === 'ERR_REF009') and
                ($payment['RefStatus'] === RefundStatus::CANCELLED) and
                ($refundAmount === $input['payment']['amount']))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_REFUND,
                    [
                        'message' => 'Payment was already cancelled at this point by billdesk',
                        'payment_id' => $input['payment']['id']
                    ]);

                return;
            }

            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                [$content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED);
        }
    }

    public function verify(array $input)
    {
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $attributes['TxnAmount'] = $attributes['amount'];
        $payment->setPaymentId($attributes['reference_no']);

        $payment->fill($attributes);
        $payment->setAction($this->action);
        $payment->saveOrFail();

        return $payment;
    }

    protected function getAuthRequestContentArray($input)
    {
        if (!empty($input['payment']['bank'])) 
        {
            $bankId = BankCodes::$bankCodeMap[$input['payment']['bank']];
        }

        $content = array(
            'channel' => '0',
            'account_id' => '20640',
            'reference_no' => $input['payment']['id'],
            'amount' => $input['payment']['amount']/100,


            'return_url' => $input['callbackUrl'],
            //'return_url' => 'http://DUMMY_RETURN_URL',
            
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
            'description' => 'live paymnet',

            'currency' => 'INR',
            'mode' => 'TEST', //$this->mode,
            //'payment_option' => '1007',
            //'bank_code' => '1007',
            //'payment_mode' => '3',
        );
        
        if ($this->mode ==='test')//$this->mode === ModeEbs::TEST)
        {
            $content['channel'] = '2';
            $content['name_on_card'] = 'TEST';
            $content['card_number'] = '4111111111111111';
            $content['card_expiry'] = '0717';
            $content['payment_mode'] = '1';
            $content['card_brand'] = '1';
            $content['card_cvv'] ='123';
         
        }
         

        $content['secure_hash'] = $this->getSecureHash($content);
  //      dd($content);
        return $content;
    }

    protected function getSecureHash($content)
    {
        $secretKey = 'bd9c562902844435bcba33ee9528a4b3';
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
