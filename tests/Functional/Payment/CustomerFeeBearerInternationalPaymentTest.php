<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\Helpers\EntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CustomerFeeBearerInternationalPaymentTest extends TestCase
{
    use PaymentTrait;

    const AMOUNT = 600000;
    const FEE = 14160;
    const TAX = 2160;

    const MCC_MARKDOWN = (1-(Entity::DEFAULT_MCC_MARKDOWN_PERCENTAGE)/100);

    protected function setup(): void
    {
        parent::setUp();
        $this->ba->publicAuth();
        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL]);
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER, 'percent_rate' => 200, 'fixed_rate' => 0]);
        $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCustomerFeeBearerWithDCC()
    {

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => self::AMOUNT, 'currency' => 'INR', 'iin' => '401201'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);

        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];
        $forexRate = $flowsResponse['all_currencies'][$cardCurrency]['forex_rate'];
        $markUp = $flowsResponse['all_currencies'][$cardCurrency]['conversion_percentage']/100;

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        // DCC CALCULATIONS

        $dccAmount = ceil(self::AMOUNT * $forexRate * (1 + $markUp));
        $dccFee = ceil(self::FEE * $forexRate * (1 + $markUp));
        $dccTax = ceil(self::TAX * $forexRate * (1 + $markUp));
        $dccTotalAmount = $dccAmount + $dccFee;

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals(self::AMOUNT + self::FEE, $calculateFeesResponse['input']['amount']);
        self::assertEquals(self::FEE, $calculateFeesResponse['input']['fee']);
        self::assertEquals(self::TAX, $calculateFeesResponse['input']['tax']);
        self::assertEquals($dccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($dccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($dccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($dccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals($cardCurrency, $calculateFeesResponse['display']['currency']);
        self::assertArrayNotHasKey('mcc_request_id', $calculateFeesResponse['input']);

        $paymentCreateRequest = [
            'currency'              => 'INR',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => self::AMOUNT + self::FEE,
            'fee'                   => self::FEE,
            'dcc_currency'          => $cardCurrency,
            'currency_request_id'   => $currencyRequestId,
            '_'                     => ['library' => 'checkoutjs']
        ];

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);

        // CALLING PAYMENT CAPTURE WITH THE ORIGINAL AMOUNT

        $this->capturePayment($paymentCreateResponse['razorpay_payment_id'],
            self::AMOUNT, 'INR', self::AMOUNT+self::FEE);

        $payment = $this->getEntityById('payment', $paymentCreateResponse['razorpay_payment_id'], true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        self::assertEquals("captured", $payment['status']);
        self::assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        self::assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        self::assertEquals($dccTotalAmount, $paymentMeta['gateway_amount']);

        // REFUNDING THE PAYMENT

        $refund = $this->refundPayment($payment['id']);
        self::assertEquals($payment['amount'], $refund['amount']);
    }

    public function testCustomerFeeBearerWithMCC()
    {

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND MCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'USD',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            ],
        ];

        // MCC CALCULATIONS

        $mccAmount = self::AMOUNT;
        $mccFee = (int) ceil(self::FEE * self::MCC_MARKDOWN);
        $mccTax = (int) ceil(self::TAX * self::MCC_MARKDOWN);
        $mccTotalAmount = $mccAmount + $mccFee;

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals($mccTotalAmount, $calculateFeesResponse['input']['amount']);
        self::assertEquals($mccFee, $calculateFeesResponse['input']['fee']);
        self::assertEquals($mccTax, $calculateFeesResponse['input']['tax']);
        self::assertEquals($mccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($mccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($mccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($mccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals('USD', $calculateFeesResponse['display']['currency']);

        $paymentCreateRequest = [
            'currency'              => 'USD',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => $mccTotalAmount,
            'fee'                   => $mccFee,
            'mcc_request_id'        => $calculateFeesResponse['input']['mcc_request_id'],
            '_'                     => ['library' => 'checkoutjs']
        ];

        // REFRESH RATES TO VERIFY NO RATE REVISION THROUGHOUT PAYMENT UX JOURNEY

        $currencyRefreshRequest = [
            'url'     => '/currency/USD/rates',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();
        $currencyRefreshResponse = $this->makeRequestAndGetContent($currencyRefreshRequest);

        self::assertNotEmpty($currencyRefreshResponse['INR']);

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $this->ba->publicAuth();
        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);


        // CALLING PAYMENT CAPTURE WITH THE ORIGINAL AMOUNT

        $this->capturePayment($paymentCreateResponse['razorpay_payment_id'],
            self::AMOUNT, 'USD', $mccTotalAmount);

        $payment = $this->getEntityById('payment', $paymentCreateResponse['razorpay_payment_id'], true);

        self::assertEquals("captured", $payment['status']);

        // REFUNDING THE PAYMENT

        $refund = $this->refundPayment($payment['id']);
        self::assertEquals($payment['amount'], $refund['amount']);
    }

    public function testCustomerFeeBearerWithDCConMCC()
    {
        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => self::AMOUNT, 'currency' => 'EUR', 'iin' => '401201'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);

        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];
        $forexRate = $flowsResponse['all_currencies'][$cardCurrency]['forex_rate'];
        $markUp = $flowsResponse['all_currencies'][$cardCurrency]['conversion_percentage']/100;

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND MCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'EUR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        // MCC CALCULATIONS

        $mccAmount = self::AMOUNT;
        $mccFee = (int) ceil(self::FEE * self::MCC_MARKDOWN);
        $mccTax = (int) ceil(self::TAX * self::MCC_MARKDOWN);
        $mccTotalAmount = $mccAmount + $mccFee;

        $dccAmount = ceil($mccAmount * $forexRate * (1 + $markUp));
        $dccFee = ceil($mccFee * $forexRate * (1 + $markUp));
        $dccTax = ceil($mccTax * $forexRate * (1 + $markUp));
        $dccTotalAmount = ceil($mccTotalAmount * $forexRate * (1 + $markUp));

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals($mccTotalAmount, $calculateFeesResponse['input']['amount']);
        self::assertEquals($mccFee, $calculateFeesResponse['input']['fee']);
        self::assertEquals($mccTax, $calculateFeesResponse['input']['tax']);
        self::assertEquals($dccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($dccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($dccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($dccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals('USD', $calculateFeesResponse['display']['currency']);

        $paymentCreateRequest = [
            'currency'              => 'EUR',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => $mccTotalAmount,
            'fee'                   => $mccFee,
            'mcc_request_id'        => $calculateFeesResponse['input']['mcc_request_id'],
            'dcc_currency'          => $cardCurrency,
            'currency_request_id'   => $currencyRequestId,
            '_'                     => ['library' => 'checkoutjs']
        ];

        // REFRESH RATES TO VERIFY NO RATE REVISION THROUGHOUT PAYMENT UX JOURNEY

        $currencyRefreshRequest = [
            'url'     => '/currency/USD/rates',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();
        $currencyRefreshResponse = $this->makeRequestAndGetContent($currencyRefreshRequest);

        self::assertNotEmpty($currencyRefreshResponse['INR']);

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $this->ba->publicAuth();
        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);

        // CALLING PAYMENT CAPTURE WITH THE ORIGINAL AMOUNT

        $this->capturePayment($paymentCreateResponse['razorpay_payment_id'],
            self::AMOUNT, 'USD', $mccTotalAmount);

        $payment = $this->getEntityById('payment', $paymentCreateResponse['razorpay_payment_id'], true);

        self::assertEquals("captured", $payment['status']);

        // REFUNDING THE PAYMENT

        $refund = $this->refundPayment($payment['id']);
        self::assertEquals($payment['amount'], $refund['amount']);
    }

    public function testCustomerFeeBearerWithoutDCCorMCC()
    {

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            ],
        ];

        // DCC CALCULATIONS

        $dccAmount = self::AMOUNT;
        $dccFee = self::FEE;
        $dccTax = self::TAX;
        $dccTotalAmount = $dccAmount + $dccFee;

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals(self::AMOUNT + self::FEE, $calculateFeesResponse['input']['amount']);
        self::assertEquals(self::FEE, $calculateFeesResponse['input']['fee']);
        self::assertEquals(self::TAX, $calculateFeesResponse['input']['tax']);
        self::assertEquals($dccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($dccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($dccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($dccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals('INR', $calculateFeesResponse['display']['currency']);
        self::assertArrayNotHasKey('mcc_request_id', $calculateFeesResponse['input']);

        $paymentCreateRequest = [
            'currency'              => 'INR',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => self::AMOUNT + self::FEE,
            'fee'                   => self::FEE,
            '_'                     => ['library' => 'checkoutjs']
        ];

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);

        // CALLING PAYMENT CAPTURE WITH THE ORIGINAL AMOUNT

        $this->capturePayment($paymentCreateResponse['razorpay_payment_id'],
            self::AMOUNT, 'INR', self::AMOUNT+self::FEE);

        $payment = $this->getEntityById('payment', $paymentCreateResponse['razorpay_payment_id'], true);

        self::assertEquals("captured", $payment['status']);

        // REFUNDING THE PAYMENT

        $refund = $this->refundPayment($payment['id']);
        self::assertEquals($payment['amount'], $refund['amount']);
    }

    public function testCustomerFeeBearerWithMCCDashboardView()
    {
        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND MCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'USD',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            ],
        ];

        // MCC CALCULATIONS

        $mccAmount = self::AMOUNT;
        $mccFee = (int) ceil(self::FEE * self::MCC_MARKDOWN);
        $mccTax = (int) ceil(self::TAX * self::MCC_MARKDOWN);
        $mccTotalAmount = $mccAmount + $mccFee;

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals($mccTotalAmount, $calculateFeesResponse['input']['amount']);
        self::assertEquals($mccFee, $calculateFeesResponse['input']['fee']);
        self::assertEquals($mccTax, $calculateFeesResponse['input']['tax']);
        self::assertEquals($mccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($mccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($mccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($mccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals('USD', $calculateFeesResponse['display']['currency']);

        $paymentCreateRequest = [
            'currency'              => 'USD',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => $mccTotalAmount,
            'fee'                   => $mccFee,
            'mcc_request_id'        => $calculateFeesResponse['input']['mcc_request_id'],
            '_'                     => ['library' => 'checkoutjs']
        ];

        // REFRESH RATES TO VERIFY NO RATE REVISION THROUGHOUT PAYMENT UX JOURNEY

        $currencyRefreshRequest = [
            'url'     => '/currency/USD/rates',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();
        $currencyRefreshResponse = $this->makeRequestAndGetContent($currencyRefreshRequest);

        self::assertNotEmpty($currencyRefreshResponse['INR']);

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $this->ba->publicAuth();
        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);

        // CALLING FETCH PAYMENT CALL TO CHECK THE FEE_BASE_AMOUNT

        $paymentArray = $this->fetchPayment($paymentCreateResponse['razorpay_payment_id']);

        self::assertNotNull($paymentArray['fee_currency_amount']);
        self::assertEquals($mccFee*10,$paymentArray['fee_currency_amount']);

        // CHECK FOR PAYMENT_META TABLE ENTRY

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        self::assertEquals(true,$paymentMeta['mcc_applied']);
        self::assertEquals(10,$paymentMeta['mcc_forex_rate']);
        self::assertEquals(number_format((1-self::MCC_MARKDOWN)*100,2),$paymentMeta['mcc_mark_down_percent']);
        self::assertEquals(false,$paymentMeta['dcc_offered']);
        self::assertNull($paymentMeta['forex_rate']);
        self::assertNull($paymentMeta['dcc_mark_up_percent']);
    }

    public function testCustomerFeeBearerWithDCConMCCDashBoardView()
    {
        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => self::AMOUNT, 'currency' => 'EUR', 'iin' => '401201'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);

        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];
        $forexRate = $flowsResponse['all_currencies'][$cardCurrency]['forex_rate'];
        $markUp = $flowsResponse['all_currencies'][$cardCurrency]['conversion_percentage']/100;

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND MCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'EUR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        // MCC CALCULATIONS

        $mccAmount = self::AMOUNT;
        $mccFee = (int) ceil(self::FEE * self::MCC_MARKDOWN);
        $mccTax = (int) ceil(self::TAX * self::MCC_MARKDOWN);
        $mccTotalAmount = $mccAmount + $mccFee;

        $dccAmount = ceil($mccAmount * $forexRate * (1 + $markUp));
        $dccFee = ceil($mccFee * $forexRate * (1 + $markUp));
        $dccTax = ceil($mccTax * $forexRate * (1 + $markUp));
        $dccTotalAmount = ceil($mccTotalAmount * $forexRate * (1 + $markUp));

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals($mccTotalAmount, $calculateFeesResponse['input']['amount']);
        self::assertEquals($mccFee, $calculateFeesResponse['input']['fee']);
        self::assertEquals($mccTax, $calculateFeesResponse['input']['tax']);
        self::assertEquals($dccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($dccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($dccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($dccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals('USD', $calculateFeesResponse['display']['currency']);

        $paymentCreateRequest = [
            'currency'              => 'EUR',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => $mccTotalAmount,
            'fee'                   => $mccFee,
            'mcc_request_id'        => $calculateFeesResponse['input']['mcc_request_id'],
            'dcc_currency'          => $cardCurrency,
            'currency_request_id'   => $currencyRequestId,
            '_'                     => ['library' => 'checkoutjs']
        ];

        // REFRESH RATES TO VERIFY NO RATE REVISION THROUGHOUT PAYMENT UX JOURNEY

        $currencyRefreshRequest = [
            'url'     => '/currency/USD/rates',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();
        $currencyRefreshResponse = $this->makeRequestAndGetContent($currencyRefreshRequest);

        self::assertNotEmpty($currencyRefreshResponse['INR']);

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $this->ba->publicAuth();
        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);


        // CALLING FETCH PAYMENT CALL TO CHECK THE FEE_BASE_AMOUNT

        $paymentArray = $this->fetchPayment($paymentCreateResponse['razorpay_payment_id']);

        self::assertNotNull($paymentArray['fee_currency_amount']);
        self::assertEquals($mccFee*10,$paymentArray['fee_currency_amount']);

        // CHECK FOR PAYMENT_META TABLE ENTRY

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        self::assertEquals(true,$paymentMeta['mcc_applied']);
        self::assertEquals(10,$paymentMeta['mcc_forex_rate']);
        self::assertEquals(number_format((1-self::MCC_MARKDOWN)*100,2),$paymentMeta['mcc_mark_down_percent']);
        self::assertEquals(true,$paymentMeta['dcc_offered']);
        self::assertEquals($forexRate,$paymentMeta['forex_rate']);
        self::assertEquals($markUp*100,$paymentMeta['dcc_mark_up_percent']);


    }

    public function testCustomerFeeBearerWithDCCDashboardView()
    {
        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => self::AMOUNT, 'currency' => 'INR', 'iin' => '401201'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);

        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];
        $forexRate = $flowsResponse['all_currencies'][$cardCurrency]['forex_rate'];
        $markUp = $flowsResponse['all_currencies'][$cardCurrency]['conversion_percentage']/100;

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        // DCC CALCULATIONS

        $dccAmount = ceil(self::AMOUNT * $forexRate * (1 + $markUp));
        $dccFee = ceil(self::FEE * $forexRate * (1 + $markUp));
        $dccTax = ceil(self::TAX * $forexRate * (1 + $markUp));
        $dccTotalAmount = $dccAmount + $dccFee;

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals(self::AMOUNT + self::FEE, $calculateFeesResponse['input']['amount']);
        self::assertEquals(self::FEE, $calculateFeesResponse['input']['fee']);
        self::assertEquals(self::TAX, $calculateFeesResponse['input']['tax']);
        self::assertEquals($dccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($dccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($dccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($dccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals($cardCurrency, $calculateFeesResponse['display']['currency']);
        self::assertArrayNotHasKey('mcc_request_id', $calculateFeesResponse['input']);

        $paymentCreateRequest = [
            'currency'              => 'INR',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => self::AMOUNT + self::FEE,
            'fee'                   => self::FEE,
            'dcc_currency'          => $cardCurrency,
            'currency_request_id'   => $currencyRequestId,
            '_'                     => ['library' => 'checkoutjs']
        ];

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);

        // CALLING FETCH PAYMENT CALL TO CHECK THE FEE_BASE_AMOUNT

        $paymentArray = $this->fetchPayment($paymentCreateResponse['razorpay_payment_id']);

        // CHECK FOR PAYMENT_META TABLE ENTRY

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        self::assertEquals(false,$paymentMeta['mcc_applied']);
        self::assertNull($paymentMeta['mcc_forex_rate']);
        self::assertNull($paymentMeta['mcc_mark_down_percent']);
        self::assertEquals(true,$paymentMeta['dcc_offered']);
        self::assertEquals($forexRate,$paymentMeta['forex_rate']);
        self::assertEquals($markUp*100,$paymentMeta['dcc_mark_up_percent']);

    }

    public function testOrderCreationEntityCheckCFBMCCPayments()
    {
        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => self::AMOUNT, 'currency' => 'EUR', 'iin' => '401201'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);

        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];
        $forexRate = $flowsResponse['all_currencies'][$cardCurrency]['forex_rate'];
        $markUp = $flowsResponse['all_currencies'][$cardCurrency]['conversion_percentage']/100;

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND MCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => self::AMOUNT,
                'currency'              => 'EUR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        // MCC CALCULATIONS

        $mccAmount = self::AMOUNT;
        $mccFee = (int) ceil(self::FEE * self::MCC_MARKDOWN);
        $mccTax = (int) ceil(self::TAX * self::MCC_MARKDOWN);
        $mccTotalAmount = $mccAmount + $mccFee;

        $dccAmount = ceil($mccAmount * $forexRate * (1 + $markUp));
        $dccFee = ceil($mccFee * $forexRate * (1 + $markUp));
        $dccTax = ceil($mccTax * $forexRate * (1 + $markUp));
        $dccTotalAmount = ceil($mccTotalAmount * $forexRate * (1 + $markUp));

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);

        self::assertEquals($mccTotalAmount, $calculateFeesResponse['input']['amount']);
        self::assertEquals($mccFee, $calculateFeesResponse['input']['fee']);
        self::assertEquals($mccTax, $calculateFeesResponse['input']['tax']);
        self::assertEquals($dccAmount/100, $calculateFeesResponse['display']['original_amount']);
        self::assertEquals($dccFee/100, $calculateFeesResponse['display']['fees']);
        self::assertEquals($dccTax/100, $calculateFeesResponse['display']['tax']);
        self::assertEquals($dccTotalAmount/100, $calculateFeesResponse['display']['amount']);
        self::assertEquals('USD', $calculateFeesResponse['display']['currency']);

        $order = $this->fixtures->create('order', ['amount' => $mccAmount,'currency' => 'EUR']);

        $paymentCreateRequest = [
            'currency'              => 'EUR',
            'method'                => 'card',
            'email'                 => 'qa.testing@razorpay.com',
            'contact'               => '+918888888888',
            'card'                  => ['number' => '4012010000000007', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 24],
            'amount'                => $mccTotalAmount,
            'fee'                   => $mccFee,
            'mcc_request_id'        => $calculateFeesResponse['input']['mcc_request_id'],
            'dcc_currency'          => $cardCurrency,
            'currency_request_id'   => $currencyRequestId,
            'order_id'              => $order->getPublicId(),
            '_'                     => ['library' => 'checkoutjs']
        ];

        // REFRESH RATES TO VERIFY NO RATE REVISION THROUGHOUT PAYMENT UX JOURNEY

        $currencyRefreshRequest = [
            'url'     => '/currency/USD/rates',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();
        $currencyRefreshResponse = $this->makeRequestAndGetContent($currencyRefreshRequest);

        self::assertNotEmpty($currencyRefreshResponse['INR']);

        // CALLING PAYMENT CREATE API VIA AJAX ROUTE

        $this->ba->publicAuth();
        $paymentCreateResponse = $this->doAuthPaymentViaAjaxRoute($paymentCreateRequest);


        // CALLING FETCH PAYMENT CALL TO CHECK THE FEE_BASE_AMOUNT

        $paymentArray = $this->fetchPayment($paymentCreateResponse['razorpay_payment_id']);

        self::assertNotNull($paymentArray['fee_currency_amount']);
        self::assertEquals($mccFee*10,$paymentArray['fee_currency_amount']);

        // CHECK FOR PAYMENT_META TABLE ENTRY

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        self::assertEquals(true,$paymentMeta['mcc_applied']);
        self::assertEquals(10,$paymentMeta['mcc_forex_rate']);
        self::assertEquals(number_format((1-self::MCC_MARKDOWN)*100,2),$paymentMeta['mcc_mark_down_percent']);
        self::assertEquals(true,$paymentMeta['dcc_offered']);
        self::assertEquals($forexRate,$paymentMeta['forex_rate']);
        self::assertEquals($markUp*100,$paymentMeta['dcc_mark_up_percent']);

        $this->capturePayment($paymentCreateResponse['razorpay_payment_id'],
            self::AMOUNT, 'EUR', $mccTotalAmount);

        $order = $this->getLastEntity('order', true);

        self::assertEquals($mccAmount,$order['amount_paid']);
        self::assertEquals('paid', $order['status']);
    }

}
