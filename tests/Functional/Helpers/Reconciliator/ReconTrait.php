<?php

namespace RZP\Tests\Functional\Helpers\Reconciliator;

use Mockery;
use Illuminate\Http\UploadedFile;

trait ReconTrait
{
    protected function generateReconFile()
    {
        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $this->gateway,
            'content' => [],
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

    protected function mockReconContentFunction($closure, $gateway = null)
    {
        $recon = $this->mockRecon()
                      ->shouldReceive('content')
                      ->andReturnUsing($closure)
                      ->mock();

        $this->setMockRecon($recon, $gateway);

        return $recon;
    }

    protected function mockRecon($gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        $class = $this->app['gateway']->getReconClass($gateway);

        return Mockery::mock($class, [])->makePartial();
    }
}
