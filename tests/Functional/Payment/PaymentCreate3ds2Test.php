<?php


namespace Functional\Payment;


use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;

class PaymentCreate3ds2Test extends TestCase
{

    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getPaymentArray();

        $this->ba->privateAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    private function getPaymentArray()
    {
        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['notes']['protocol'] = '3ds2';
        return $paymentArray;
    }

    public function testPaymentCreateWithS2S3ds2Payment()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);
        $this->assertArrayHasKey('next', $responseContent);
        $this->assertArrayHasKey('action', $responseContent['next'][0]);
        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/authenticate');

        $url = $this->getPaymentRedirectToAuthorizrUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');
        $browser['java_enabled'] = $content['browser[java_enabled]'];
        $browser['javascript_enabled'] = $content['browser[javascript_enabled]'];
        $browser['timezone_offset'] = $content['browser[timezone_offset]'];
        $browser['color_depth'] = $content['browser[color_depth]'];
        $browser['screen_width'] = $content['browser[screen_width]'];
        $browser['screen_height'] = $content['browser[screen_height]'];
        $auth_step = $content['auth_step'];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));
        $this->assertEquals("3ds2Auth", $content['auth_step']);
        $content = [];
        $content['browser'] = $browser;
        $content['auth_step'] = $auth_step;
        $secondAuthRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];

        $secondAuthResponse=$this->makeRequestParent($secondAuthRequest);
        $this->ba->publicAuth();

        $content_auth = $secondAuthResponse->getContent();
        list($secondAuthUrl, $method) = $this->getFormDataFromResponse($content_auth, 'http://localhost');
        $this->assertNotNull($secondAuthUrl);
        $this->assertEquals("POST", $method);
    }

    public function testPaymentCreateWithAjax3ds2Payment()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['enable_3ds2']);
        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();
        $infoResponse = $this->makeRequestParent($request);
        $content = $infoResponse->getContent();
        $data = json_decode($content, TRUE);

        $this->assertEquals("redirect", $data['type']);
        $this->assertTrue($this->isRedirectToAuthorizeUrl($data['request']['url']));
        $this->assertEquals("POST", $data['request']['method']);

        $url = $data['request']['url'];

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();

        list($auth_url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');
        $browser['java_enabled'] = $content['browser[java_enabled]'];
        $browser['javascript_enabled'] = $content['browser[javascript_enabled]'];
        $browser['timezone_offset'] = $content['browser[timezone_offset]'];
        $browser['color_depth'] = $content['browser[color_depth]'];
        $browser['screen_width'] = $content['browser[screen_width]'];
        $browser['screen_height'] = $content['browser[screen_height]'];
        $auth_step = $content['auth_step'];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($auth_url));
        $this->assertEquals("3ds2Auth", $content['auth_step']);
        $content = [];
        $content['browser'] = $browser;
        $content['auth_step'] = $auth_step;
        $secondAuthRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$auth_url
        ];

        $secondAuthResponse=$this->makeRequestParent($secondAuthRequest);
        $this->ba->publicAuth();

        $content_auth = $secondAuthResponse->getContent();
        list($secondAuthUrl, $method) = $this->getFormDataFromResponse($content_auth, 'http://localhost');
        $this->assertNotNull($secondAuthUrl);
        $this->assertEquals("POST", $method);
    }

    public function testPaymentCreateWithAjaxNativeOTP3ds2Payment()
    {
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();
        $infoResponse = $this->makeRequestParent($request);
        $content = $infoResponse->getContent();
        $data = json_decode($content, TRUE);

        $this->assertEquals("otp", $data['type']);
        self::assertArrayHasKey('next', $data);
        self::assertArrayHasKey('submit_url', $data);
        self::assertArrayHasKey('resend_url', $data);
        self::assertEquals('otp_submit', $data['next'][0]);
        self::assertEquals('otp_resend', $data['next'][1]);
    }

    public function testPaymentCreateWithAjax3ds2PaymentWithoutMerchantFeature()
    {
        $payment = $this->getDefaultPaymentArray();
        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();
        $infoResponse = $this->makeRequestParent($request);
        $content = $infoResponse->getContent();
        $data = json_decode($content, TRUE);

        $this->assertEquals("first", $data['type']);
        self::assertArrayHasKey('url', $data['request']);
        self::assertArrayHasKey('callback_url', $data['request']['content']);
    }

    public function testPaymentCreateWithCheckout3ds2Payment()
    {
        $payment = $this->payment;
        $request = [
            'content' => $payment,
            'url'     => '/payments/create/checkout',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();
        $infoResponse = $this->makeRequestParent($request);
        $content = $infoResponse->getContent();

        list($auth_url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');
        $browser['java_enabled'] = $content['browser[java_enabled]'];
        $browser['javascript_enabled'] = $content['browser[javascript_enabled]'];
        $browser['timezone_offset'] = $content['browser[timezone_offset]'];
        $browser['color_depth'] = $content['browser[color_depth]'];
        $browser['screen_width'] = $content['browser[screen_width]'];
        $browser['screen_height'] = $content['browser[screen_height]'];
        $auth_step = $content['auth_step'];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($auth_url));
        $this->assertEquals("3ds2Auth", $content['auth_step']);
        $content = [];
        $content['browser'] = $browser;
        $content['auth_step'] = $auth_step;
        $secondAuthRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$auth_url
        ];

        $secondAuthResponse=$this->makeRequestParent($secondAuthRequest);
        $this->ba->publicAuth();

        $content_auth = $secondAuthResponse->getContent();
        list($secondAuthUrl, $method) = $this->getFormDataFromResponse($content_auth, 'http://localhost');
        $this->assertNotNull($secondAuthUrl);
        $this->assertEquals("POST", $method);
    }


}
