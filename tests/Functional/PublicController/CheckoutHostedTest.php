<?php

namespace RZP\Tests\Functional\Contacts;

use Mockery;
use RZP\Models\Admin\Org;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use DB;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;

use Illuminate\Support\Facades\View;

class CheckoutHostedTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . "/CheckoutHostedTestData.php";

        parent::setUp();

        $this->ba->publicAuth();
    }

    protected function setMockRazorxTreatment(
        array $razorxTreatment,
        string $defaultBehaviour = "control"
    ) {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(["getTreatment"])
            ->getMock();

        $this->app->instance("razorx", $razorxMock);

        $this->app->razorx->method("getTreatment")->will(
            $this->returnCallback(function ($mid, $feature, $mode) use (
                $razorxTreatment,
                $defaultBehaviour
            ) {
                if (array_key_exists($feature, $razorxTreatment) === true) {
                    return $razorxTreatment[$feature];
                }

                return strtolower($defaultBehaviour);
            })
        );
    }

    protected function generateHDFCCheckout2ViewMocks($orderId, $mode, $customCode)
    {
        $arr = [
            "key" => " rzp_" . $mode . "_LtX0CbrmyiGV5j",
            "options" =>
                '{"key":" rzp_' .
                $mode .
                '_LtX0CbrmyiGV5j","order_id":"' .
                $orderId .
                '","name":"Shopify Test Store","prefill":{"email":"test@razorpay.com"},"notes":{"mode":"' .
                $mode .
                '","shopify_order_id":"rQFI1IJ6T2yPiryRCTGjZ3pLx","referer_url":" https:\/\/shoes-store-testing-rzp.myshopify.com\/"},"_":{"integration":"shopify","integration_version":"shopify-payment-app"},"__referer":"https:\/\/shoes-store-testing-rzp.myshopify.com\/","callback_url":"https:\/\/shoes-store-testing-rzp.myshopify.com\/"}',
            "meta" => '{"type":"hdfcvas","custom_code":"'.$customCode.'","checkout_logo_url":null,"custom_checkout_logo_enabled":false,"rmv_cc_text_from_logo":true}',
            "script" =>
                "https://cdn.razorpay.com/static/hosted/standard-vas.js",
            "urls" => "{}",
        ];
        $resp = ["type" => "hdfc_checkout_2"];

        View::shouldReceive("make")
            ->with("public.embedded", $arr)
            ->andReturn($resp);
    }

    protected function generateHDFCCheckout2ViewMocksWithCCText($orderId, $mode, $customCode)
    {
        $arr = [
            "key" => " rzp_" . $mode . "_LtX0CbrmyiGV5j",
            "options" =>
                '{"key":" rzp_' .
                $mode .
                '_LtX0CbrmyiGV5j","order_id":"' .
                $orderId .
                '","name":"Shopify Test Store","prefill":{"email":"test@razorpay.com"},"notes":{"mode":"' .
                $mode .
                '","shopify_order_id":"rQFI1IJ6T2yPiryRCTGjZ3pLx","referer_url":" https:\/\/shoes-store-testing-rzp.myshopify.com\/"},"_":{"integration":"shopify","integration_version":"shopify-payment-app"},"__referer":"https:\/\/shoes-store-testing-rzp.myshopify.com\/","callback_url":"https:\/\/shoes-store-testing-rzp.myshopify.com\/"}',
            "meta" => '{"type":"hdfcvas","custom_code":"'.$customCode.'","checkout_logo_url":null,"custom_checkout_logo_enabled":false,"rmv_cc_text_from_logo":true}',
            "script" =>
                "https://cdn.razorpay.com/static/hosted/standard-vas.js",
            "urls" => "{}",
        ];
        $resp = ["type" => "hdfc_checkout_2"];

        View::shouldReceive("make")
            ->with("public.embedded", $arr)
            ->andReturn($resp);
    }

    protected function generateHostedViewMocks($orderId)
    {
        $arr = [
            "options" =>
                '{"key":" rzp_test_LtX0CbrmyiGV5j","order_id":"' .
                $orderId .
                '","name":"Shopify Test Store","prefill":{"email":"test@razorpay.com"},"notes":{"mode":"test","shopify_order_id":"rQFI1IJ6T2yPiryRCTGjZ3pLx","referer_url":" https:\/\/shoes-store-testing-rzp.myshopify.com\/"},"_":{"integration":"shopify","integration_version":"shopify-payment-app"},"__referer":"https:\/\/shoes-store-testing-rzp.myshopify.com\/"}',
            "checkout" => "https://checkout.razorpay.com/v1/checkout.js",
            "urls" =>
                '{"callback":"https:\/\/shoes-store-testing-rzp.myshopify.com\/","cancel":"https:\/\/shoes-store-testing-rzp.myshopify.com\/"}',
            "url_callback" => "https://shoes-store-testing-rzp.myshopify.com/",
            "retry" => true,
        ];
        $resp = ["type" => "hosted"];

        View::shouldReceive("make")
            ->with("public.hosted", $arr)
            ->andReturn($resp);
    }

    protected function generateHDFCCheckout2ViewMocksWithOrg($orderId, $mode, $customCode)
    {
        $arr = [
            "key" => " rzp_" . $mode . "_LtX0CbrmyiGV5j",
            "options" =>
                '{"key":" rzp_' .
                $mode .
                '_LtX0CbrmyiGV5j","order_id":"' .
                $orderId .
                '","name":"Shopify Test Store","prefill":{"email":"test@razorpay.com"},"notes":{"mode":"' .
                $mode .
                '","shopify_order_id":"rQFI1IJ6T2yPiryRCTGjZ3pLx","referer_url":" https:\/\/shoes-store-testing-rzp.myshopify.com\/"},"_":{"integration":"shopify","integration_version":"shopify-payment-app"},"__referer":"https:\/\/shoes-store-testing-rzp.myshopify.com\/","callback_url":"https:\/\/shoes-store-testing-rzp.myshopify.com\/"}',
            "meta" => '{"type":"hdfcvas","custom_code":"'.$customCode.'","checkout_logo_url":null,"custom_checkout_logo_enabled":false,"rmv_cc_text_from_logo":true}',
            "script" =>
                "https://cdn.razorpay.com/static/hosted/standard-vas.js",
            "urls" => "{}",
        ];
        $resp = ["type" => "hdfc_checkout_2"];

        View::shouldReceive("make")
            ->with("public.embedded", $arr)
            ->andReturn($resp);
    }

    public function testHdfcCheckout2Hit()
    {
        $this->setMockRazorxTreatment([
            RazorxTreatment::HDFC_CHECKOUT_2 => "on",
        ]);

        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit("10000000000000", [
            "org_id" => $org->getId(),
        ]);
        $this->ba->directAuth();
        $order = $this->fixtures->create("order");
        $orderId = "order_" . $order->getId();

        $this->testData[__FUNCTION__] = $this->testData["testHdfcCheckout2Hit"];
        $this->testData[__FUNCTION__]["request"]["content"]["checkout"][
            "order_id"
        ] = $orderId;

        $this->fixtures->merchant->addFeatures(["hdfc_checkout_2"]);
        $this->generateHDFCCheckout2ViewMocks($orderId, "test",  $org->getCustomCode());
        $this->startTest();
    }

    public function testHdfcCheckout2HitWithCCText()
    {
        $this->setMockRazorxTreatment([
            RazorxTreatment::HDFC_CHECKOUT_2 => "on",
        ]);

        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit("10000000000000", [
            "org_id" => $org->getId(),
        ]);
        $this->ba->directAuth();
        $order = $this->fixtures->create("order");
        $orderId = "order_" . $order->getId();

        $this->testData[__FUNCTION__] = $this->testData["testHdfcCheckout2HitWithCCText"];
        $this->testData[__FUNCTION__]["request"]["content"]["checkout"][
        "order_id"
        ] = $orderId;

        $this->fixtures->merchant->addFeatures(["hdfc_checkout_2","rmv_cc_text_from_logo"]);
        $this->generateHDFCCheckout2ViewMocksWithCCText($orderId, "test",  $org->getCustomCode());
        $this->startTest();
    }

    public function testHdfcCheckout2HitWithOrg()
    {

        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit("10000000000000", [
            "org_id" => $org->getId(),
        ]);
        $this->ba->directAuth();
        $order = $this->fixtures->create("order");
        $orderId = "order_" . $order->getId();

        $this->testData[__FUNCTION__] = $this->testData["testHdfcCheckout2HitWithOrg"];
        $this->testData[__FUNCTION__]["request"]["content"]["checkout"][
        "order_id"
        ] = $orderId;

        $this->fixtures->merchant->addFeatures(["rmv_cc_text_from_logo"]);
        $this->fixtures->org->addFeatures([FeatureConstants::HDFC_CHECKOUT_2],$org->getId());

        $this->generateHDFCCheckout2ViewMocksWithOrg($orderId, "test",  $org->getCustomCode());
        $this->startTest();
    }

    public function testHdfcCheckout2HitLiveMode()
    {
        $this->setMockRazorxTreatment([
            RazorxTreatment::HDFC_CHECKOUT_2 => "on",
        ]);

        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit("10000000000000", [
            "org_id" => $org->getId(),
        ]);
        $this->ba->directAuth();
        $order = $this->fixtures->create("order");
        $orderId = "order_" . $order->getId();

        $this->testData[__FUNCTION__] =
            $this->testData["testHdfcCheckout2HitLiveMode"];
        $this->testData[__FUNCTION__]["request"]["content"]["checkout"][
            "order_id"
        ] = $orderId;

        $this->fixtures->merchant->addFeatures(["hdfc_checkout_2"]);
        $this->generateHDFCCheckout2ViewMocks($orderId, "live",  $org->getCustomCode());
        $this->startTest();
    }

    public function testHdfcCheckout2NotHit()
    {
        $this->setMockRazorxTreatment([
            RazorxTreatment::HDFC_CHECKOUT_2 => "control",
        ]);

        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit("10000000000000", [
            "org_id" => $org->getId(),
        ]);
        $this->ba->directAuth();
        $order = $this->fixtures->create("order");
        $orderId = "order_" . $order->getId();

        $this->testData[__FUNCTION__] =
            $this->testData["testHdfcCheckout2NotHit"];
        $this->testData[__FUNCTION__]["request"]["content"]["checkout"][
            "order_id"
        ] = $orderId;

        $this->fixtures->merchant->addFeatures(["hdfc_checkout_2"]);
        $this->generateHostedViewMocks($orderId);
        $this->startTest();
    }
}
