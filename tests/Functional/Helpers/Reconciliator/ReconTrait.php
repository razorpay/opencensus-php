<?php

namespace RZP\Tests\Functional\Helpers\Reconciliator;

use Mockery;
use RZP\Models\Merchant;
use Illuminate\Http\UploadedFile;
use RZP\Models\Base\PublicEntity;

trait ReconTrait
{
    protected function generateReconFile($content = [])
    {
        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $this->gateway,
            'content' => $content,
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function reconcile(UploadedFile $uploadedFile, $gateway)
    {
        $input = [
            'manual'           => true,
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        return $this->makeRequestAndGetContent($request)[0];
    }

    protected function setMockRecon($recon, $gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        $this->app['gateway']->setRecon($gateway, $recon);
    }

    protected function mockReconContentFunction($closure, $gateway = null, array $input = [])
    {
        $gateway = $gateway ?: $this->gateway;

        $recon = $this->mockRecon($gateway, $input)
                      ->shouldReceive('content')
                      ->andReturnUsing($closure)
                      ->mock();

        $this->setMockRecon($recon, $gateway);

        return $recon;
    }

    protected function mockRecon($gateway = null, array $input = [])
    {
        $gateway = $gateway ?: $this->gateway;

        $class = $this->app['gateway']->getReconClass($gateway, $input);

        return Mockery::mock($class, [])->makePartial();
    }

    protected function makePaymentsSince(int $createdAt, int $count = 3)
    {
        $payments = [];

        for ($i = 0; $i < $count; $i++)
        {
            $payment = $this->createPayment();

            $this->fixtures->edit(
                'payment',
                $payment,
                [
                    'created_at'    => $createdAt,
                    'authorized_at' => $createdAt + 10
                ]);

            $payments[] = $payment;
        }

        return $payments;
    }

    private function createPayment()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => $this->method,
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction', ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create($this->method, ['payment_id' => $payment->getId()]);

        return $payment->getId();
    }
}
