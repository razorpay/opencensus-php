<?php

namespace RZP\Tests\Functional\Helpers\QrCode;

use RZP\Models\Order;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;

trait NonVirtualAccountQrCodeTrait
{
    private function createQrCode(array $input = [], $mode = 'test', $merchantId = '10000000000000')
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $defaultValues = $this->getDefaultQrCodeRequestArray();

        $attributes = array_merge($defaultValues, $input);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/qr_codes',
            'content' => $attributes,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function processRefund($id, $mode = 'test', $merchantId = '10000000000000')
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/'. $id . '/refund',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function closeQrCode(string $id)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/qr_codes/'.$id.'/close',
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultQrCodeRequestArray()
    {
        return [
            'name'         => 'Test QR Code',
            'description'  => 'QR code for tests',
            'usage'        => 'multiple_use',
            'type'         => 'bharat_qr',
            'fixed_amount' => '0',
            'notes'        => [
                'a' => 'b',
            ],
        ];
    }

    private function fetchQrPayment(string $id = null)
    {
        $url = '';
        if ($id === null)
        {
            $url = '/payments/qr_payments';

        }
        else
        {
            $url = '/payments/qr_codes/' . $id . '/payments';
        }
        $request = [
            'method' => 'GET',
            'url'    => $url,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchQrCode(string $id = null, $input = [])
    {
        if ($id === null)
        {
            $url = '/payments/qr_codes';
        }
        else
        {
            $url = '/payments/qr_codes/' . $id;
        }

        $request = [
            'method'  => 'GET',
            'url'     => $url,
            'content' => $input
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function makeUpiIciciPayment($request)
    {
        $this->ba->directAuth();

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        return $response;
    }

    public function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    public function ecollectValidateVpa($gateway, $vpaPrefix, $input)
    {
        $url = '/test/ecollect/validate/' . $gateway . '/' . $vpaPrefix;

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'raw'     => $input
        ];

        $response = $this->makeRequestAndGetRawContent($request);

        return $response;
    }

    private function createQrCodeForCheckout($order = null, $amount = null)
    {
        $this->ba->publicAuth();

        if ($order !== null)
        {
            $input[Entity::ENTITY_TYPE] = 'order';
            $input[Entity::ENTITY_ID] = $order->getPublicId();
        }
        else
        {
            $input[Entity::REQ_AMOUNT] = $amount;
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/checkout/qr_codes',
            'content' => $input,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchPaymentByQrCodeIdOnCheckout(string $id)
    {
        $this->ba->publicAuth();

        $url = '/checkout/qr_code/' . $id . '/payment';

        $request = [
            'method'  => 'GET',
            'url'     => $url,
        ];

        return $this->makeRequestAndGetContent($request);
    }
}
