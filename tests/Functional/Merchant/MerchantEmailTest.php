<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Event;
use Mail;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Email\Type;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class MerchantEmailTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantEmailTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    /**
     * Return the json data
     * @param array $attributes
     *
     * @return array
     */
    public function get_data(array $attributes = array())
    {
        $defaultValues = [
            'type'   => 'refund',
            'email'  => 'cvhg@gmail.com,abc@gmail.com',
            'phone'  => '9732097320',
            'policy' => 'tech',
            'url'    => 'https://razorpay.com'
        ];

        $newAttributes = array_merge($defaultValues,$attributes);

        return $newAttributes;
    }

    /**
     * Asserts that the function returns the expected array same as given input
     *
     */
    public function testCreateMerchantEmails()
    {
        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $testData   = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $testData['request']['url'] = "/merchants/{$merchantId}/additionalemail";

        $this->startTest();
    }

    /**
     * Asserts that the function returns the expected array  of different type of emails  for a particular merchant
     * stored in database
     *
     */
    public function testFetchMerchantEmails()
    {
        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $this->fixtures->create(
            'merchant_email', $this->get_data(['type' => 'chargeback'])
        );

        $this->fixtures->create(
            'merchant_email', $this->get_data()
        );


        $testData                   = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/{$merchantId}/additionalemail";

        $this->ba->adminAuth();

        $this->startTest();
    }


    /**
     * Asserts that the function deletes the type of email for a Given Merchant
     *
     */
    public function testDeleteMerchantEmails()
    {
        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $type       = Type::REFUND;

        $this->fixtures->create(
            'merchant_email', $this->get_data()

        );

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/{$merchantId}/additionalemail/{$type}";

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Asserts that the function fetches the type of email for a Given Merchant
     *
     */
    public function testFetchMerchantEmailsByType()
    {
        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $type       = Type::REFUND;

        $this->fixtures->create(
            'merchant_email', $this->get_data()
        );

        $testData                   = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/{$merchantId}/additionalemail/{$type}";

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Asserts that the function throws error for a Given Merchant and Type if it do not exist
     *
     */
    public function testFetchEmailAndTypeNotExists()
    {
        $merchantId                 = Constants::DEFAULT_MERCHANT_ID;

        $type                       = Type::REFUND;

        $testData                   = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/{$merchantId}/additionalemail/{$type}";

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function saveMerchantSupportPhoneDetails()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => $merchantId,
            'role'        => 'owner',
        ]);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $user->id);

        $this->startTest();
    }

    public function testAddMerchantSupportDetails()
    {
        $this->saveMerchantSupportPhoneDetails();

        $supportDetails  = $this->getLastEntity('merchant_email', true);

        $this->assertEquals('9732097321', $supportDetails['phone']);

        $this->assertNull($supportDetails['email']);
    }

    public function testUpdateMerchantSupportDetails()
    {
        $this->fixtures->merchant_email->create([
            'type'  => 'support',
            'phone' => '9732097320'
        ]);

        $this->saveMerchantSupportPhoneDetails();

        $supportDetails  = $this->getLastEntity('merchant_email', true);

        $this->assertEquals('9732097321', $supportDetails['phone']);

        $this->assertNull($supportDetails['email']);
    }

    public function testGetMerchantSupportDetailsPresentSuccess()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user->id,
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->merchant_email->create([
                                                    'merchant_id' => $merchantId,
                                                    'type'        => 'support',
                                                    'email'       => 'abcd@razorpay.com',
                                                    'phone'       => '9876543210',
                                                    'url'         => 'https://www.abcd.com'
                                                ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user->id);

        $this->startTest();

        $supportDetails = $this->getLastEntity('merchant_email', true);

        $this->assertEquals('9876543210', $supportDetails['phone']);

        $this->assertEquals('abcd@razorpay.com', $supportDetails['email']);

        $this->assertEquals('https://www.abcd.com', $supportDetails['url']);
    }

    public function testGetMerchantSupportDetailsNotPresentSuccess()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user->id,
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->merchant_email->create([
                                                    'merchant_id' => $merchantId,
                                                    'type'        => 'support',
                                                    'email'       => null,
                                                    'phone'       => null,
                                                    'url'         => null
                                                ]);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $user->id);

        $this->startTest();

        $supportDetails  = $this->getLastEntity('merchant_email', true);

        $this->assertNull( $supportDetails['phone']);

        $this->assertNull($supportDetails['email']);

        $this->assertNull($supportDetails['url']);
    }
}

