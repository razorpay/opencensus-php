<?php

namespace RZP\Tests\Functional\Helpers\Reconciliator;

use Excel;
use Mockery;
use RZP\Models\FileStore;
use Illuminate\Http\UploadedFile;
use RZP\Excel\Export as ExcelExport;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Excel\ExportSheet as ExcelSheetExport;

trait ReconTrait
{
    protected function generateReconFile($content = [])
    {
        // TODO: Get this vetted
        $this->ba->adminAuth();

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $this->gateway,
            'content' => $content,
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function reconcile(UploadedFile $uploadedFile, $gateway, $forceAuthorizePayments = [], $manualFile = false)
    {
        $this->ba->h2hAuth();

        $input = [
            'manual'           => true,
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        if (empty($forceAuthorizePayments) === false)
        {
            foreach ($forceAuthorizePayments as $forceAuthorizePayment)
            {
                $input[Base::FORCE_AUTHORIZE][] = $forceAuthorizePayment;
            }
        }

        if ($manualFile === true)
        {
            $input[Base::MANUAL_RECON_FILE] = 1;
        }

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        return $content[0] ?? $content;
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
        return array_reduce(
                array_fill(0, $count, 0),
                function($carry, $item) use ($createdAt)
                {
                    $payment = $this->createPayment();

                    $this->fixtures->edit(
                        'payment',
                        $payment,
                        [
                            'created_at'    => $createdAt,
                            'authorized_at' => $createdAt + 10
                        ]);

                    $carry[] = $payment;

                    return $carry;
                },
                []);
    }

    private function createPayment($content = [])
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

        $transaction = $this->fixtures->create(
            'transaction',
            [
                'entity_id'   => $payment->getId(),
                'merchant_id' => '10000000000000',
            ]
        );

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        return $payment->getId();
    }

    protected function getExcelString($name, $sheets)
    {
        $excel = (new ExcelExport)->setSheets(function() use ($sheets) {
            $sheetsInfo = [];
            foreach ($sheets as $sheetName => $data)
            {
                $sheetsInfo[$sheetName] = (new ExcelSheetExport($data['items']))->setTitle($sheetName)->setStartCell($data['config']['start_cell'])->generateAutoHeading(true);
            }

            return $sheetsInfo;
        });

        $data = $excel->raw('Xlsx');

        return $data;
    }

    protected function createFile($content, string $type = FileStore\Type::MOCK_RECONCILIATION_FILE, string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::TXT)
            ->content($content)
            ->name('testReconFile')
            ->sheetName('Sheet 1')
            ->store($store)
            ->type($type)
            ->headers(true)
            ->save();

        $file = $creator->get();

        return ['local_file_path' => $file['local_file_path']];
    }

    protected function getNewUpiEntity($merchantId, $gateway, $mockServer = null)
    {
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $payment = $this->getDefaultUpiPaymentArray($gateway);

        $payment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->gateway = 'upi_mindgate';

        $content = ($mockServer ? $mockServer : $this->mockServer())->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $gatewayPayment = $this->getDbLastEntityToArray('upi');

        return $gatewayPayment;
    }

    protected function getNewAxisUpiEntity($merchantId, $gateway)
    {
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $payment = $this->getDefaultUpiPaymentArray($gateway);

        $payment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->gateway = 'upi_axis';

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $gatewayPayment = $this->getDbLastEntityToArray('upi');

        return $gatewayPayment;
    }

    protected function getNewUpiHulkEntity($merchantId, $gateway, $overrideTxnId = true)
    {
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        if ((isset($this->payment['_']['flow']) === true) and
            ($this->payment['_']['flow'] === 'intent') and
            ($overrideTxnId === true))
        {
            $this->mockServerContentFunction(
                function (& $content, $action = null)
                {
                    if ($action === 'callback')
                    {
                        $content['data'] = array_merge($content['data'], [
                            'txn_id' => 'HDF2C8B_RANDOM_STRING_RANDOM_STRING',
                        ]);
                    }
                    else
                    {
                        $content['txn_id'] = 'HDF2C8B_RANDOM_STRING_RANDOM_STRING';
                    }
                });
        }

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upiEntity = $this->getDbLastEntity('upi');

        $content = $this->getMockServer($gateway)->getAsyncCallbackRequest($upiEntity, $payment);

        $response = $this->makeRequestAndGetContent($content);

        $upiEntity->reload();

        $payment->reload();

        return $upiEntity->toArrayAdmin();
    }

    protected function buildUnexpectedPaymentRequest()
    {
        $this->fixtures->merchant->createAccount('100DemoAccount');
        $this->fixtures->merchant->enableUpi('100DemoAccount');

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        unset($content['upi']['account_number']);
        unset($content['upi']['ifsc']);
        unset($content['upi']['gateway_data']);

        $content['terminal']['gateway']             = $this->gateway;
        $content['terminal']['gateway_merchant_id'] = $this->terminal->getGatewayMerchantId();
        $content['upi']['gateway_merchant_id']      = $this->terminal->getGatewayMerchantId();

        return $content;
    }

    protected function makeUnexpectedPaymentAndGetContent(array $content)
    {
        $request = [
            'url' => '/payments/create/upi/unexpected',
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/upi/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function buildUpiAuthorizeFailedPaymentRequest(string $payment_id)
    {
        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment_id;

        $content['upi']['gateway'] = $this->gateway;

        return $content;
    }

     protected function makeUpdatePostReconRequestAndGetContent(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
