<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Gateway\Base\Action;

trait PaymentIsgTrait
{
    protected function createBharatQrPayment($content)
    {
        $request = [
            'url'     => '/payment/callback/bharatqr/isg',
            'method'  => 'post',
            'content' => $content,
        ];

        $qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $this->getMockServer('isg')->fillBharatQrCallback($request['content'], $qrCode);

        $this->mockServerContentFunction(function (&$content, $action = null) use ($request)
        {
            if ($action === Action::VERIFY)
            {
                $content = $request['content'];
            }
        }, $this->gateway);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = [
            'url'     => '/virtual_accounts',
            'method'  => 'post',
            'content' => [
                'receiver_types' => 'qr_code'
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }
}
