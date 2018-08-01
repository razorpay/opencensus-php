<?php

namespace RZP\Gateway\Upi\Axis;

use Elasticsearch\Endpoints\FieldStats;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use phpseclib\Crypt\RSA;


class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    /**
     * @var Crypto
     */
    protected $aesCrypto;

    const ACQUIRER = 'axis';

    protected $gateway = Payment\Gateway::UPI_AXIS;

    protected $response;

    const BANK = 'axis';

    const TIMEOUT = 20;

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razaorpay@axis';

    const FIELD_LENGTH = [
        Action::AUTHORIZE => 17,
        Action::REFUND => 20,
        Action::VERIFY => 14,
    ];

    protected $map = [
        Entity::VPA => Entity::VPA,
        Entity::RECEIVED => Entity::RECEIVED,
        Entity::EXPIRY_TIME => Entity::EXPIRY_TIME,
        Entity::TYPE => Entity::TYPE,
        Fields::UNQ_TXN_ID => Entity::PAYMENT_ID,
        Fields::UNQ_CUST_ID => Entity::PAYMENT_ID,
        Fields::AMOUNT => Entity::AMOUNT,
        Fields::MERCH_ID => Entity::GATEWAY_MERCHANT_ID,
        Fields::EXPIRY => Entity::EXPIRY_TIME,
        Fields::CUSTOMER_VPA => Entity::VPA,
        Fields::MOB_NO => Entity::CONTACT,
        Fields::TXN_REFUND_ID => Entity::REFUND_ID,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);
        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $response = $this->fetchToken($input);
        $this->updateGatewayPaymentEntity($gatewayPayment, $response);
        if($response[Fields::CODE] == '000')
        {
            parent::action($input, Action::AUTHORIZE);
            $request =  $this->getCollectRequestArray($response);
            $request['headers'] = [
                'Content-Type' => 'application/json',
            ];
            s($request);
            $response1 = $this->sendGatewayRequest($request);
            $response1 = $this->parseGatewayResponse($response1->body);
            s($response1);
        }

        else
        {
            throw new \Exception();
        }

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

//        $str = '2nOrIStsX1i4G/iNdqCNpI3XPTtn3EIospF/ONroO4dM7FTMZ8uDDnkX2mMCmL1n3z9beQN0TYGM\nmuZHLVt6zVHuGQ5tWunadIxvqU2dzdS5pVfUapt2Y8u8z/hjF8rmcQ61UE9nzkMTfWiPjVmrh0lo\nL53Kg9vtyz5incS3YK7hOYAquCgBx5YQ9Ceud1JVZHdhkiks0xzNC8hyFX42xCwyvrwMIrzrM+GH\nGDl8Nn8aCMrh6frRaMBvhZxmPO87SOSlb28DB4oh695zdntyMeiLva4+9rfMGuZR90sWcsNe5g33\nZLxE0LFZoTHooktjYmYHf/4h9yucMOym+mxEkfmx6nY0+tLexlaU6SnR8AjCAZYy+OG4oIt+gfhj\nM+Zfb/fKA83pC1I21tfTFRWcx7ftdEkKWIIj7TqcO2HhCYRvv5JiTVuU9RrEjJeeI3Gk4N4FhDh/\nNzZ56pAlHA0Kg/VvzaU5jA/Wvhl7T0wq4Xnx/C+m/sV2/I2JlADdkX6SZM6DRRp10PQq/C8GRkIP\nmevNvu9oh7topaalxUAFWmgfS6RYCLhb9swAZlJbhb1KYWQ9tixZvPMtqB/HhXHJ9oqtaPxbUq09\nqYVxr/GqKGGRqSbTVmJFlY+dSPXvjSAhCotODHKLewLZOa2Q347A0AJO6mctXjpq4SM56W0biQf9\nfL5PSGjjdDw9MaK8GavrLHoSDoioLr3gGbwFXvpSN77LwqJ6xeByYhIjXdcA0Lap7Xl69mAjDbzM\nUjmBxuSEC8YTyGg+PKrYwdmgOEhZU2f9ZUnI8NlX0LNh2hcguiFkqArN2Ll58JCULotCkbSgdJx/\nO028N/GQG/ssTnQDi2hjqphqay5HGbk2Veo68hgOJ6IMupAX6g5qaEruUbw2DgN4nuZdj221M3q0\nOlm9a3D+HKGGx57XsJ1xTAuBnGubqhKdzl6UNOF8pvZIqgkfbJb0BuyKENvQnOlrgipW0cquIuLD\nrz5c3TUL1NQOfCM9VaJhal08WBZPffhCSna/BPPeTWD+Kqb+z4WdYWVuL5kAJo9+lXiYe9rxwv4a\nXrChRa/ZTML8H7zerHz/v5qO5jvHhvTHilxNK+UzHzezxVoR1jBgCt13D0ZT/D6wDChc7NfR+IrZ\nauUhQklJwQIVV8uUZmx0E4k2vASdlFTJClWk4gz7rApadmS+JZTakFk=';
        $str = 's0//6WG1iLfIhqthvk8JRtpilV+bYHuamiSVshlZ8OK4mFyhf1SZTac6AZ3jXlh1PL0il+sLMsTWMt/jPH29/Xq3qrkDjeIiVm7GsgDczJ191GAw/eJODXQ+mZ0YcGw+2eV7W/5pRufA/ggHLoEX2aZ59yig7DNw/pzXhBIE75KOplbeO13dp8IqlztL4B1nFhItLEejG+Tvg8uqGx8kloiqULh2ieA+kK2GdePLhBBqt/rDhRv+c0+WT4aVF59kHNP2/JK9eRjl4UH9SiCe8OgvHptDmVgda6t6OK2ZMYTcmq82c3Dhy+3i0jTtnJ8NPvUCWv3dSrJKB/wfvhXU+2lVo0Yu7nP29HepfA/f+iG1cDQVfbz5lVdThIiET98IHah+hX1OfFYvb5/747qN97IIDfTwbLRJhUelnLtPQEoY0DB0+L3jGm7Xo5S68xfFriDi47p72HTuHAYiMsqH3BFWhXKrT2culcVzmVmTzTWrAgEt0nDhjUiO1ngwxTDo6BNQSkg54W5ImhPq/VuX03ixJP3tYdFmU815wHP2Qp729C7L+ieNZiDeZZByx9DK1SlWIn/Sazf0GYp2texT6nivapitLMIWCFh6AN9SL7zwRiiHtGzztSzJnpMbgTUuJnpkv6I0nRPnu6p4eCdAsWa80JsVrH9ccd1bmJ8MuUbnT20WY6JM8Gvj4j0APtFgj+Xqu5izTFytuYx8emXc7VyrExgQQadclvsBwlS3dI+dVlVlXp1lSxCFWTNACDxpXSv1rhyFfth5SqCGzJl5NoGV7Q5BLeGoEIK6ILJ65bxTdWOd8d6h2i9MljS7g/HAYxUm+d0UNnfE6YyKmc04Q74o9rWf00dqt2r0+V3NY+Kpoy5kE5NjF7pCYKOrYG4qWfPEPoHTnT8IJAxKQb86y7UQQXwBbKTDOb6MyEIAMEFQHSbDAsds5lZbL65jFPsKkN/FnPJaLjqMHI1FW73RCUkdoMnkvMjGRV02UK4q17XQqGQOOvovkHsbBABpENTwdATKTwLKJrKg+oi2ATLNA/eNlQ8AtAgpJriO8U6aOK0lGWmZkcjwk1yGbxMzJWIFodhvxYAJJWYfazmP7pCfJX+GSXMhvekaOMrjChq8bZAfRj3I5/9moaEYc6uUHX37djN7rqjxaQ6N1TZRtgWaa2KZfAVAz6joip4Aevb3cNQ=';
//        $str = '{"data":"2nOrIStsX1i4G/iNdqCNpI3XPTtn3EIospF/ONroO4dM7FTMZ8uDDnkX2mMCmL1n3z9beQN0TYGM\nmuZHLVt6zVHuGQ5tWunadIxvqU2dzdS5pVfUapt2Y8u8z/hjF8rmcQ61UE9nzkMTfWiPjVmrh0lo\nL53Kg9vtyz5incS3YK7hOYAquCgBx5YQ9Ceud1JVZHdhkiks0xzNC8hyFX42xCwyvrwMIrzrM+GH\nGDl8Nn8aCMrh6frRaMBvhZxmPO87SOSlb28DB4oh695zdntyMeiLva4+9rfMGuZR90sWcsNe5g33\nZLxE0LFZoTHooktjYmYHf/4h9yucMOym+mxEkfmx6nY0+tLexlaU6SnR8AjCAZYy+OG4oIt+gfhj\nM+Zfb/fKA83pC1I21tfTFRWcx7ftdEkKWIIj7TqcO2HhCYRvv5JiTVuU9RrEjJeeI3Gk4N4FhDh/\nNzZ56pAlHA0Kg/VvzaU5jA/Wvhl7T0wq4Xnx/C+m/sV2/I2JlADdkX6SZM6DRRp10PQq/C8GRkIP\nmevNvu9oh7topaalxUAFWmgfS6RYCLhb9swAZlJbhb1KYWQ9tixZvPMtqB/HhXHJ9oqtaPxbUq09\nqYVxr/GqKGGRqSbTVmJFlY+dSPXvjSAhCotODHKLewLZOa2Q347A0AJO6mctXjpq4SM56W0biQf9\nfL5PSGjjdDw9MaK8GavrLHoSDoioLr3gGbwFXvpSN77LwqJ6xeByYhIjXdcA0Lap7Xl69mAjDbzM\nUjmBxuSEC8YTyGg+PKrYwdmgOEhZU2f9ZUnI8NlX0LNh2hcguiFkqArN2Ll58JCULotCkbSgdJx/\nO028N/GQG/ssTnQDi2hjqphqay5HGbk2Veo68hgOJ6IMupAX6g5qaEruUbw2DgN4nuZdj221M3q0\nOlm9a3D+HKGGx57XsJ1xTAuBnGubqhKdzl6UNOF8pvZIqgkfbJb0BuyKENvQnOlrgipW0cquIuLD\nrz5c3TUL1NQOfCM9VaJhal08WBZPffhCSna/BPPeTWD+Kqb+z4WdYWVuL5kAJo9+lXiYe9rxwv4a\nXrChRa/ZTML8H7zerHz/v5qO5jvHhvTHilxNK+UzHzezxVoR1jBgCt13D0ZT/D6wDChc7NfR+IrZ\nauUhQklJwQIVV8uUZmx0E4k2vASdlFTJClWk4gz7rApadmS+JZTakFk\u003d"}';
        s($this->decryptAes($str));

        $str2 = '{
"customerVpa":"padma@axis",
"merchantId":"OLA",
"merchantChannelId":"OLAAPP",
"merchantTransactionId":"e1b80bce4ea8404185781ebc075745e4", "transactionTimestamp":"24-MAR-17",
"transactionAmount":"72",
"gatewayTransactionId":"AXI91123456789032025071490711853337",
"gatewayResponseCode":"00",
"gatewayResponseMessage":"Success",
"rrn":"703118109867", "checksum":"1E23D89E669F6E922A69E26573CD9A85379EDB3A984879DF53E6E3499FB31365AF4 5E343A6CB3B35D14C45E900E6A639D50D47274349EF0D69BFB110CD7FD20B53B449D36127550 006644B2C28036C60949190E867A2CCD548086AD67DB5FC83AAC0A7262083C88BAE95E60E589 A2708DB2C76378E07028417223B968D73B4143166AAF4EB3F2F261FC11FD4B8D30EFD095B4F42 A9AA78218E7D612A3B101BC517184E77D8335F29C78E68FDE73A5F712B9E094E1D44521D503E2 19BB5D9BDF3F52559F80DF58B83F02814FF7F989AE8ED1048DA7A2FBF4816D48DE7F513E149E CCC164B1B439FA3B00B282915DE3B585B61EA03E0F86DC53088C3771A859615"
}';
//        $str2 = '{
//"customerVpa":"neerajrzp@axis",
//"merchantId":"RAZAORPAY",
//"merchantChannelId":"RAZAORPAYAPP",
//"merchantTransactionId":"Ag8qKOyXjqCuIa",
//"transactionTimestamp":"2018-08-01T12:30:53+05:30",
//"transactionAmount":"3.5",
//"gatewayTransactionId":"AXI91860545641481786401534004101878",
//"gatewayResponseCode":"000",
//"gatewayResponseMessage":"Success",
//"rrn":"821312037726",
//"checksum":"5228DC2368DDD09ABE42D8D8BD15FB74B427F22585F1F4917511F602387353D63A92FFE10A46345F01451CABB6BF7F8F0E554D618A616E0E15EB729911D6EDB78291025EDB5FE1DF361CD33028753A26E6EAF71116DDB768B09F292A25E326F08DED6F720E3D83A3E4102C6103A7B8DEA04CBE88E2001F0B66969C2C0921C29505937B38C557A9BA83BA8E6C416E4B6ED7FB2EB1A672E20AA8E24E3C474ADBD07F91216AB9C154B4878813233D9391BCFC9979E28EE5BA219029EC8B085114A6223400B2D6658658F21CEC39DA4984CAD8264FB96E0C3F07A006D7E782C8159369CF606C53F0CB63B97B9A727BF68075DE9E313BE9B79AB3743F06EAC93DA4D5"
//}';

        s($this->encryptAes($str2));
        s($str);

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    protected function fetchToken($input)
    {
        parent::action($input, Action::FETCH_TOKEN);

        $request =  $this->getTokenRequestArray($input);
        $request['headers'] = [
            'Content-Type' => 'application/json',
        ];
        s($request);
        $response = $this->sendGatewayRequest($request);
        $response = $this->parseGatewayResponse($response->body);
        s($response);
        return $response;
    }

    /**
     * We only store the VPA because the rest of the fields
     * are filled by the callback
     *
     * @param  array $input
     * @param string $action
     *
     * @return array
     */
    protected function getGatewayEntityAttributes(array $input, string $action = Action::AUTHORIZE)
    {
        $attrs = [
            Entity::GATEWAY_MERCHANT_ID => 'RAZAORPAY',
            Entity::VPA                 => $input['payment']['vpa'],
            Entity::ACTION              => $action,
            Entity::TYPE                => Base\Type::COLLECT,
        ];

        if ($action === Action::REFUND)
        {
            $attrs[Entity::REFUND_ID] = $input['refund']['id'];
        }

        if ($action === Action::AUTHORIZE)
        {
            $attrs[Entity::EXPIRY_TIME] = $input['upi']['expiry_time'];
        }

        return $attrs;
    }

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
     */
    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getGatewayMerchantId();
        }

        return $this->config['test_merchant_id'];
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data): string
    {
        $json = json_encode($data);

        return $json;
    }

    /**
     * @param $responseBody
     * @param string $type
     * @return array
     * @see https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
     */
    protected function parseGatewayResponse($responseBody, $type = Action::COLLECT)
    {
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'encrypted'         => true,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);
        return $this->jsonToArray($responseBody);
    }

    private function checkResponseStatus(string $status, string $successStatus = Status::SUCCESS)
    {
        if ($status !== $successStatus)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));
        }
    }

    protected function getTokenRequestArray($input)
    {
        $payment = $input['payment'];

        $data = [
            Fields::MERCH_ID => 'RAZAORPAY',
            Fields::MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::UNQ_TXN_ID => $payment['id'],
            Fields::UNQ_CUST_ID => $payment['id'],
            Fields::AMOUNT => $this->formatAmount($payment['amount']),
            Fields::TXN_DTL => $this->getPaymentRemark($input),
            Fields::CURRENCY => 'INR',
            Fields::ORDER_ID => 'ORDERID',
            Fields::CUSTOMER_VPA => $payment['vpa'],
            Fields::EXPIRY => (string) $input['upi']['expiry_time'],
            Fields::S_ID => '',
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $payment['id'],
            ]);
        return $request;
    }

    protected function getCollectRequestArray($input,$content=[],$method = 'post', $type = null)
    {
        $request = array(
            'url'       => $this->getUrl($type).$input[Fields::DATA],
            'method'    => $method,
            'content'   => $content,
        );
        return $request;

    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     *
     * @param array $input
     *
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $paymentDescription = $input['payment']['description'] ?? '';
        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;


        $description = trim($description);
        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string.
     *                  Default value is 6012, as per HDFC
     *                  (Check pgtech group)
     */
    protected function getMerchantCategoryCode(array $input)
    {
        return $input['merchant']['category'] ?: '6012';
    }

    /**
     * Handles the S2S callback
     * @param  array $input
     * @return boolean
     */
    public function callback(array $input): array
    {
        s($input);
        parent::callback($input);

        $content = $input['gateway'];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        if ($gatewayPayment->getType() !== Base\Type::PAY)
        {
            assertTrue($content[ResponseFields::UPI_TXN_ID] === $gatewayPayment->getGatewayPaymentId());
        }

        assertTrue($input['payment']['id'] === $content[ResponseFields::PAYMENT_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($content[ResponseFields::STATUS]);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        // Gateways must return array in callback
        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attributes = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attributes[Entity::RECEIVED] = 1;

        $payment->fill($attributes);

        $payment->generatePspData($attributes);

        $this->repo->saveOrFail($payment);
    }

    public function verify(array $input)
    {
        parent::verify($input);
        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function runPaymentVerifyFlow($verify)
    {
        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify);

        if (($gatewayPayment === null) and
            ($this->shouldReturnIfPaymentNullInVerifyFlow($verify)))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'payment_id' => $verify->input['payment']['id'],
                    'message'    => 'payment id not found in the gateway database',
                    'gateway'    => $this->gateway
                ]
            );

            return null;
        }

        $this->sendPaymentVerifyRequest($verify);
        $this->verifyPayment($verify);
        s($verify->match);
        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id' => $this->input['payment']['id'],
                    'gateway'    => $this->gateway
                ]
            );
        }
        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        return $verify->getDataToTrace();
    }

    protected function getPaymentVerifyRequestArray($input)
    {
        $payment = $input['payment'];

//        $checksumdata='RAZAORPAY'.'RAZAORPAYAPP'.$payment['id'];
//        $checksum = "";
//        openssl_public_encrypt($checksumdata,$checksum,'');

        $data = [
            Fields::CHECK_STATUS_MERCH_ID => 'RAZAORPAY',
            Fields::CHECK_STATUS_MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::CHECK_STATUS_UNQ_TXN_ID => $payment['id'],
            Fields::CHECK_STATUS_MOBILE_NO => '918605456414',
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECK_STATUS_CHECKSUM] = bin2hex($checksum);

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'application/json'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data
            ]);

        s($request);
        return $request;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

//        $bankDetails = $this->parseBankAccountDetails($content[Fields::BANK_REFERENCE]);

//        $content = array_merge($content, $bankDetails);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function parseBankAccountDetails($bankReference)
    {
        $fields = constant(__NAMESPACE__ . '\ResponseFields::BANK_DETAILS');

        $values = explode(Fields::BANK_REFERENCE_SEPARATOR, $bankReference);

        $bankReferenceArray = [];

        $index = 0;

        if (empty($values) === false)
        {
            foreach ($fields as $key)
            {
                if ($values[$index] !== Fields::NO_BANK_DETAIL)
                {
                    $bankReferenceArray[$key] = $values[$index];
                }

                $index++;
            }

        }

        return $bankReferenceArray;
    }

    protected function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            s($verify->gatewaySuccess,$verify->apiSuccess);
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $input = $verify->input;
        s($content);
        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = number_format($input['payment']['amount']/ 100, 2, '.', '');

            $actualAmount = number_format($content[Fields::DATA][Fields::TXN_AMOUNT], 2, '.', '');

            s($paymentAmount,$actualAmount);
            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $content[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($verify->payment, $content);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;
        $verify->gatewaySuccess = ($content[Fields::RESULT] === Status::SUCCESSFUL);
    }

    protected function getCipherInstance(): RSA
    {

        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    /**
     * Encrypts data before sending it to Axis
     * @param  string $data
     * @return string
     */
    protected function encrypt(string $data): string
    {
        $rsa = $this->getCipherInstance();

        $rsa->loadKey('-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl3x5rqnoQtzCsVJsrdS+
LO10KSVL76T5y5lr4Q3ci2kPwIsh/oA5tE+fhYyLjDDi+jOwSvwcOS2ZexWpImOp
OCAmxq5dowGF4dHc4AwaihV4+SjkNiRhDyyzTwhafntsbpqfLLL/f6Tk79xstIKf
lSLzCW1RQ1sUuCe/VZvYqYquDiFucW2ZI36A0XO2JrwuOkwuSXUOv1SApoVN6gLT
e2PwSyyNtQPoXO4+u3b9pXUvx5wcYO4uTpA2Ym9S6/EJm5+DjaN1c1DGdwbUITZC
nb+ZuF4oIUB8xCqmWDXZYy7Q0aO0tviHg6sFOs2MrxwjXAa2NaQbrcCnQ8bXu0qe
UQIDAQAB
-----END PUBLIC KEY-----');

        return $rsa->encrypt($data);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::REFUND);

        $refund = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::REFUND);

        $response[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($refund, $response);

        s($response);

//        $this->checkResponseStatus($response[ResponseFields::STATUS], Status::REFUND_SUCCESS);
    }

    protected function getRefundRequestArray(array $input): array
    {

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $refund = $input['refund'];
        // The order is defined in the docs
        // See README.md

        $data = [
            Fields::MERCH_ID => 'RAZAORPAY',
            Fields::MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::TXN_REFUND_ID => $this->getRefundId($refund),
            Fields::MOB_NO => '918605456414',
            Fields::TXN_REFUND_AMOUNT => $this->formatAmount($input['refund']['amount']),
            Fields::UNQ_TXN_ID => $input['payment']['id'],
            Fields::REFUND_REASON =>  $this->getRefundRemark($input),
            Fields::S_ID => '',
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'refund_id'         => $input['refund']['id'],
            ]);

        return $request;
    }

    /**
     * This is done in order to fix duplicate
     * merchant transaction id issue in case
     * refund is retried multiple times
     *
     * @return string
     */
    protected function getRefundId(array $refund)
    {
        return $refund['id'] . ($refund['attempts'] ?: '');
    }

    /**
     * Returns a refund description, capped to 50 chars
     * @param  array  $input
     * @return string
     */
    protected function getRefundRemark(array $input): string
    {
        $description = $input['merchant']->getFilteredDba();

        // Using ?: works with empty strings as well
        // (because '' == false) === true
        $description = $description ?: 'Razorpay';

        return 'Refund for ' . substr($description, 0, 36);
    }

    protected function createCryptoIfNotCreated()
    {
        if ($this->aesCrypto === null)
        {
            $this->aesCrypto = new AESCrypto(AES::MODE_ECB,'ezSbSIqWRthPAbzN');
        }
    }

    public function encryptAes(string $stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->encryptString($stringToEncrypt);
    }

    public function decryptAes(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }

}