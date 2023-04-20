<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Database\Eloquent\Factory;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Address\Entity;
use RZP\Models\Address\Repository;
use RZP\Models\Address\Type;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Address;
use RZP\Tests\Functional\Fixtures\Entity\Feature;
use RZP\Tests\Functional\Fixtures\Entity\Payment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;

class PaymentCreateAVSTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateAVSTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['avs']);
    }

    public function testPreferencesForFirstTimeAVSUser()
    {
        $preferencesResponse = $this->getPreferences();

        $items = $preferencesResponse['customer']['tokens']['items'];

        foreach ($items as $token)
        {
            $this->assertNull($token['billing_address']);
        }
    }

    public function testPreferencesRepeatedAVSUser()
    {
        list($billingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(true, 1);

        $preferencesResponse = $this->getPreferences();

        $items = $preferencesResponse['customer']['tokens']['items'];

        $tokenId = 'token_'.$paymentEntity['token_id'];

        foreach ($items as $token)
        {
            if($tokenId === $token['id'])
            {
                $this->assertNotNull($token['billing_address']);
                break;
            }
        }
    }

    public function testCreatePaymentFirstTimeAVSUserSavedCard()
    {
        list($billingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(true, 1);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);
        $this->validateCustomerTokenBillingAddress($paymentEntity, $billingAddressArray);
    }

    public function testCreatePaymentFirstTimeAVSUserNotSavedCard()
    {
        list($billingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(true);

        $this->assertNull($paymentEntity['token_id']);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);
    }

    public function testCreatePaymentRepeatedAVSUserSavedCard()
    {
        list($billingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(true, 1);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);
        $this->validateCustomerTokenBillingAddress($paymentEntity, $billingAddressArray);

        // Same customer 2nd time payment create
        list($billingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(false, 1);

        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);

        // $paymentAddressEntity will be null as billing_address not passed in the create payment request
        $this->assertNull($paymentAddressEntity);

        $customerTokenAddressEntity = (new Repository)->fetchAddressesForEntity(
            $paymentEntity->getGlobalOrLocalTokenEntity(),[Entity::TYPE => Type::BILLING_ADDRESS]);

        // there will be only 1 entry of billing_address against a token
        $this->assertCount(1, (array)$customerTokenAddressEntity->count());
    }

    public function testCreatePaymentAVSDisabled()
    {
        $this->fixtures->merchant->removeFeatures(['avs']);

        list($billingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(true, 1);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);

        $customerTokenAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity->getGlobalOrLocalTokenEntity(), Type::BILLING_ADDRESS);

        // ignore saving address if avs disabled
        $this->assertNull($customerTokenAddressEntity);
    }

    public function testCreatePaymentAVSInternationalDisabled()
    {
        $this->fixtures->merchant->disableInternational();

        $billingAddressArray = $this->getDefaultBillingAddressArray(true);

        $paymentArray = $this->getPaymentArray($billingAddressArray, 1);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        });

        $paymentEntity = $this->getDbLastPayment();

        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);

        $customerTokenAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity->getGlobalOrLocalTokenEntity(), Type::BILLING_ADDRESS);

        $this->assertNull($paymentAddressEntity);

        $this->assertNull($customerTokenAddressEntity);
    }

    public function testCreatePaymentRepeatedAVSUserDifferentBillingAddressSavedCard()
    {
        list($firstBillingAddressArray, $paymentEntity) = $this->doAVSAuthPaymentAndFetchPayment(true, 1);

        $this->validatePaymentBillingAddress($paymentEntity, $firstBillingAddressArray);
        $this->validateCustomerTokenBillingAddress($paymentEntity, $firstBillingAddressArray);

        // Same customer 2nd time payment create different billing address
        $secondBillingAddressArray = $this->getDefaultBillingAddressArray();
        $payment = $this->getPaymentArray($secondBillingAddressArray, 1);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $response = $this->doAuthPayment($payment);

        $secondPaymentEntity = $this->getDbLastPayment();

        $this->validatePaymentBillingAddress($secondPaymentEntity, $secondBillingAddressArray);

        $customerTokenAddressEntity = (new Repository)->fetchAddressesForEntity(
            $secondPaymentEntity->getGlobalOrLocalTokenEntity(),[Entity::TYPE => Type::BILLING_ADDRESS]);

        // there will be only 1 entry of billing_address against a token
        $this->assertCount(1, (array)$customerTokenAddressEntity->count());

        // customer token address remains same. It can be changed only when,
        // customer address changes at issuer bank's end
        $this->validateBillingAddress($secondBillingAddressArray, $customerTokenAddressEntity->first());
    }

    public function testCreatePaymentAVSIncorrectBillingAddress()
    {
        $billingAddress = $this->getDefaultBillingAddressArray();

        $billingAddress['line1'] = 'Razorpay Software, 1st Floor, 11, SJR Cyber';

        $paymentArray = $this->getPaymentArray($billingAddress, 1);
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        });
    }

    public function testCreatePaymentAVSWithoutBillingAddress()
    {
        $paymentArray = $this->getPaymentArray(null, 1);
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        });
    }

    public function testCreatePaymentAVSNotEnrolledCard()
    {
        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $payment = $this->getPaymentArray($billingAddressArray, 1);
        $payment['card']['number'] = '555555555555558';
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);
        $response = $this->doAuthPayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);
        $this->validateCustomerTokenBillingAddress($paymentEntity, $billingAddressArray);
    }

    public function testCreateS2SPaymentAVS()
    {
        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $payment = $this->getPaymentArray($billingAddressArray, 1);

        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->doS2SPrivateAuthPayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);
        $this->validateCustomerTokenBillingAddress($paymentEntity, $billingAddressArray);
    }

    public function testPaymentCreateWithAddressCollectJson()
    {
        $payment = $this->getPaymentArray(null, 1);
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $this->fixtures->merchant->addFeatures(['address_required']);
        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $response = $this->makeRedirectToAddressCollect($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertTrue($this->redirectToAddressCollect);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getDbLastPayment();
        $this->validatePaymentBillingAddress($paymentEntity, $this->getDefaultBillingAddressArray());

    }

    public function testPaymentCreateS2SWithoutAVS()
    {
        $payment = $this->getPaymentArray(null, 1);

        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $this->fixtures->merchant->removeFeatures(['avs']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertFalse($this->redirectToAddressCollect);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getDbLastPayment();
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);
        $this->assertNull($paymentAddressEntity);

    }

    public function testPaymentCreateWithAVSS2SRedirect()
    {
        $payment = $this->getPaymentArray(null, 1);
        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $this->fixtures->merchant->addFeatures(['address_required']);

        $this->doS2SPrivateAuthAndCapturePayment($payment);

        $this->assertTrue($this->redirectToAddressCollect);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getDbLastPayment();
        $this->validatePaymentBillingAddress($paymentEntity, $this->getDefaultBillingAddressArray());

    }

    public function testPaymentCreateWithoutAVSS2SRedirect()
    {
        $payment = $this->getPaymentArray(null, 1);
        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $this->fixtures->merchant->removeFeatures(['avs']);

        $this->doS2SPrivateAuthAndCapturePayment($payment);

        $this->assertFalse($this->redirectToAddressCollect);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getDbLastPayment();
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);
        $this->assertNull($paymentAddressEntity);

    }

    public function testCreateS2SPaymentAVSNotEnrolledCard()
    {
        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $payment = $this->getPaymentArray($billingAddressArray, 1);
        $payment['card']['number'] = '555555555555558';
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        $this->doS2SPrivateAuthPayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);

        $this->validatePaymentBillingAddress($paymentEntity, $billingAddressArray);
        $this->validateCustomerTokenBillingAddress($paymentEntity, $billingAddressArray);
    }

    public function testPaymentCreateWithAVSCustomCheckout()
    {
        $payment = $this->getPaymentArray($this->getDefaultBillingAddressArray(true), 1);
        unset($payment['save']);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::RAZORPAYJS;

        $this->fixtures->merchant->addFeatures(['address_required']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);

        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertTrue($this->redirectToAddressCollect);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $paymentEntity = $this->getDbLastPayment();
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);
        $this->assertNotNull($paymentAddressEntity);
    }

    public function testPaymentCreateWithoutAVSCustomCheckout()
    {
        $payment = $this->getPaymentArray($this->getDefaultBillingAddressArray(true), 1);
        unset($payment['save']);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::RAZORPAYJS;

        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $this->fixtures->merchant->removeFeatures(['avs']);

        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertFalse($this->redirectToAddressCollect);

        $this->assertEquals('authorized', $paymentEntity['status']);
    }

    public function testPaymentCreateWithAVSEmbeddedCheckout()
    {
        $payment = $this->getPaymentArray($this->getDefaultBillingAddressArray(true), 1);
        unset($payment['save']);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::EMBEDDED;

        $this->fixtures->merchant->addFeatures(['address_required']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);

        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertTrue($this->redirectToAddressCollect);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $paymentEntity = $this->getDbLastPayment();
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);
        $this->assertNotNull($paymentAddressEntity);
    }

    public function testPaymentCreateWithoutAVSEmbeddedCheckout()
    {
        $payment = $this->getPaymentArray($this->getDefaultBillingAddressArray(true), 1);
        unset($payment['save']);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::EMBEDDED;

        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $this->fixtures->merchant->removeFeatures(['avs']);

        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertFalse($this->redirectToAddressCollect);

        $this->assertEquals('authorized', $paymentEntity['status']);
    }

    public function testPaymentCreateWithAVSDirectCheckout()
    {
        $payment = $this->getPaymentArray($this->getDefaultBillingAddressArray(true), 1);
        unset($payment['save']);

        $this->fixtures->merchant->addFeatures(['address_required']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);

        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertTrue($this->redirectToAddressCollect);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $paymentEntity = $this->getDbLastPayment();
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);
        $this->assertNotNull($paymentAddressEntity);
    }

    public function testPaymentCreateWithAVSAndroidCheckout()
    {
        $payment = $this->getPaymentArray($this->getDefaultBillingAddressArray(true), 1);
        unset($payment['save']);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CUSTOM;

        $this->fixtures->merchant->addFeatures(['address_required']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);

        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertTrue($this->redirectToAddressCollect);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $paymentEntity = $this->getDbLastPayment();
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);
        $this->assertNotNull($paymentAddressEntity);
    }

    private function getPreferences($orderId = null, $currency = 'INR')
    {
        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'currency' => [
                    $currency
                ],
            ],
        ];

        if ($orderId !== null)
        {
            $request['content']['order_id'] = $orderId;
        }

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    private function getPaymentArray($billingAddressArray = null, $localSave = 0)
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '4012010000000007';

        $paymentArray['customer_id'] = 'cust_100000customer';

        if(isset($billingAddressArray))
        {
            $paymentArray['billing_address'] = $billingAddressArray;
        }

        $paymentArray['save'] = $localSave;

        return $paymentArray;
    }

    /**
     * @param $postal_code
     * @param $addressEntity
     */
    private function validateBillingAddress($billingAddress, $addressEntity): void
    {
        foreach (['line1', 'line2', 'city', 'state', 'country'] as $attribute) {
            $this->assertEquals($billingAddress[$attribute], $addressEntity[$attribute]);
        }

        $this->assertEquals($billingAddress['postal_code'], $addressEntity['zipcode']);
    }

    /**
     * @param $paymentEntity
     * @param array $billingAddressArray
     */
    protected function validatePaymentBillingAddress($paymentEntity, array $billingAddressArray): void
    {
        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);

        $this->assertNotNull($paymentAddressEntity);

        $this->validateBillingAddress($billingAddressArray, $paymentAddressEntity);
    }

    /**
     * @param $paymentEntity
     * @param array $billingAddressArray
     */
    protected function validateCustomerTokenBillingAddress($paymentEntity, array $billingAddressArray): void
    {
        $customerTokenAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity->getGlobalOrLocalTokenEntity(), Type::BILLING_ADDRESS);

        $this->assertEquals($paymentEntity['token_id'], $customerTokenAddressEntity['entity_id']);

        $this->assertEquals('token', $customerTokenAddressEntity['entity_type']);

        $this->validateBillingAddress($billingAddressArray, $customerTokenAddressEntity);
    }

    /**
     * @return array
     */
    protected function doAVSAuthPaymentAndFetchPayment($passBillingAddress = false, $localSave = 0): array
    {
        $billingAddressArray = null;

        if($passBillingAddress === true) {
            $billingAddressArray = $this->getDefaultBillingAddressArray(true);
        }

        $paymentArray = $this->getPaymentArray($billingAddressArray, $localSave);

        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $response = $this->doAuthPayment($paymentArray);

        $paymentEntity = $this->getDbEntityById('payment', $response['razorpay_payment_id']);
        return array($billingAddressArray, $paymentEntity);
    }

    /* When the iin doesn't have the country Details, the avs_required flag is set as false
    */
    public function testFailureAddressRequired()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $flowsData = [
            'content' => ['amount' => 5000, 'currency' => 'USD', 'card_number' => '4550034817906865'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);

        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['avs_required']);
    }

    public function testAddressRequiredForNonUSGBCACards()
    {
        $this->fixtures->merchant->addFeatures(['address_required']);

        $this->fixtures->iin->create([
                                         'iin' => '837413',
                                         'country' => 'AU',
                                         'network' => 'MasterCard',
                                     ]);

        $flowsData = [
            'content' => ['amount' => 5000, 'currency' => 'AUD', 'iin' => '837413'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);

        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['avs_required']);
    }

    public function testAddressRequiredForCardWithNoCountry()
    {
        $this->fixtures->merchant->addFeatures(['address_required']);

        $this->fixtures->iin->create([
                                         'iin' => '837413',
                                         'network' => 'MasterCard',
                                         'country' => null,
                                     ]);

        $flowsData = [
            'content' => ['amount' => 5000, 'currency' => 'AUD', 'iin' => '837413'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);

        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['avs_required']);
    }

    public function testAVSNotSupportedLibrary()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '4212345678901237';

        $paymentArray['_']['library'] = 'razorpayjs';

        $response = $this->doAuthPayment($paymentArray);

        $paymentEntity = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $paymentAddressEntity = (new Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);

        $this->assertNull($paymentAddressEntity);
    }

    public function testCreatePaymentAVSInvalidAddress()
    {
        $this->fixtures->merchant->addFeatures(['address_required']);

        $billingAddressArray = $this->getDefaultBillingAddressArray();
        unset($billingAddressArray['postal_code']);
        $payment = $this->getPaymentArray($billingAddressArray, 1);
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestException::class);

        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $billingAddressArray['postal_code'] = 'text';
        $payment = $this->getPaymentArray($billingAddressArray, 1);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestValidationFailureException::class);

        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $billingAddressArray['postal_code'] = 'text%1';
        $payment = $this->getPaymentArray($billingAddressArray, 1);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestValidationFailureException::class);

        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $billingAddressArray['postal_code'] = 'A B';
        $payment = $this->getPaymentArray($billingAddressArray, 1);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestValidationFailureException::class);

        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $billingAddressArray['line1'] = '';
        $payment = $this->getPaymentArray($billingAddressArray, 1);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestValidationFailureException::class);

        $billingAddressArray = $this->getDefaultBillingAddressArray();
        $billingAddressArray['city'] = 'c';
        $payment = $this->getPaymentArray($billingAddressArray, 1);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            BadRequestValidationFailureException::class);

    }
}
