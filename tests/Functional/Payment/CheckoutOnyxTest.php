<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Testing\TestResponse;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\TestCase;

class CheckoutOnyxTest extends TestCase
{
    protected $xssUrls = [
        'https://api.rzp.com/pay/?HouseNumber=9&AddressLine=The+Gardens<script>alert(1)</script>&AddressLine2=foxlodge+woods',
        'javascript:alert("YouHaveBeenHacked")',
        'https://example.com/?u=alert("Ha ha ha!!! You got hacked!!!")',
        'https://example.com/?u=javascript:alert("YouHaveBeenHacked")',
    ];

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CheckoutOnyxTestData.php';

        parent::setUp();
    }

    /**
     * @param array $testData
     * @dataProvider xssUrlsProvider
     */
    public function testOnyxWillNotAllowXSSInURL(array $testData): void
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The request.url format is invalid.');

        $getContent = $testData['GET'];
        $postContent = $testData['POST'];

        $this->callCheckoutOnyx($getContent, $postContent);
    }

    /**
     * @param array $testData
     * @dataProvider xssBackUrlsProvider
     */
    public function testOnyxWillNotAllowXSSInBackURL(array $testData): void
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The back format is invalid.');

        $getContent = $testData['GET'];
        $postContent = $testData['POST'];

        $this->callCheckoutOnyx($getContent, $postContent);
    }

    public function testOnyxWillNotAllowInlineJavascriptInContentKeys(): void
    {
        $testData = $this->testData[__FUNCTION__];
        $postContent = $testData['POST'];
        $getContent = $testData['GET'];

        $response = $this->callCheckoutOnyx($getContent, $postContent);

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $response->getContent());
    }

    public function testOnyxWillNotAllowInlineJavascriptInContentValues(): void
    {
        $testData = $this->testData[__FUNCTION__];
        $postContent = $testData['POST'];
        $getContent = $testData['GET'];

        $response = $this->callCheckoutOnyx($getContent, $postContent);

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $response->getContent());
    }

    public function xssUrlsProvider(): array
    {
        $requests = [];

        foreach ($this->xssUrls as $url) {
            $requestParams = [
                'GET' => [
                    'request' => [
                        'url' => $url,
                        'method' => 'POST',
                    ],
                    'options' => json_encode([
                        'key' => 'rzp_test_1DP5mmOlF5G5ag',
                        'amount' => 100,
                    ]),
                ],
                'POST' => [
                    'razorpay_payment_id' => 'pay_abcde',
                ],
            ];

            $requests[] = [$requestParams];
        }

        return $requests;
    }

    public function xssBackUrlsProvider(): array
    {
        $requests = [];

        foreach ($this->xssUrls as $url) {
            $requestParams = [
                'GET' => [
                    'request' => [
                        'url' => 'https://example.com',
                        'method' => 'POST',
                    ],
                    'options' => json_encode([
                        'key' => 'rzp_test_1DP5mmOlF5G5ag',
                        'amount' => 100,
                    ]),
                    'back' => $url,
                ],
                'POST' => [
                    'razorpay_payment_id' => 'pay_abcde',
                ],
            ];

            $requests[] = [$requestParams];
        }

        return $requests;
    }

    private function callCheckoutOnyx(array $getParams, array $postParams): TestResponse
    {
        $params = http_build_query([
            'data' => base64_encode(json_encode($getParams))
        ]);

        $url = '/v1/checkout/onyx?' . $params;

        return $this->post($url, $postParams);
    }
}
