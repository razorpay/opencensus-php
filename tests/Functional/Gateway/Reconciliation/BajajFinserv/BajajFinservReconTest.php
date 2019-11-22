<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\BajajFinserv;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class BajajFinservReconTest extends TestCase
{
    use BatchTestTrait;
    use ReconTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BajajFinservReconTestData.php';

        parent::setUp();

        $this->gateway = 'bajajfinserv';

        $this->setMockGatewayTrue();

        $this->ba->publicAuth();

        $this->setBflPaymentArray();

//        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockCardVault();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $this->fixtures->create(
            'terminal',
            [
                'id'                  => 'AqdfGh5460opVt',
                'merchant_id'         => '10000000000000',
                'gateway'             => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled'             => 1,
                'emi'                 => 1,
                'emi_duration'        => 9
            ]);

        $this->fixtures->merchant->enableEmi();
    }

    public function testPaymentRecon()
    {
//        $this->ba->publicAuth();
//
//        $this->doAuthPayment($this->payment);
//        $paymentEntity = $this->getDbLastEntity('payment');
//
//        $url = $this->getOtpSubmitUrl($paymentEntity);
//
//        $request = [
//            'request'   => [
//                'method'    => 'POST',
//                'url' => $url,
//                'content'   => [
//                    'type'  => 'otp',
//                    'otp'   => '111111'
//                ]
//            ],
//            'response'  => [
//                'content'     => [],
//                'status_code' => 200,
//            ],
//        ];
//
//        $this->runRequestResponseFlow($request);
//
//        $payment = $this->getDbLastEntityToArray('payment');
//        $mozart = $this->getDbLastEntityToArray('mozart');
//        $card = $this->getDbLastEntityToArray('card');

        $card = $this->fixtures->create(
            'card',
            [
                'merchant_id'        => '10000000000000',
                'name'               => 'Harshil',
                'expiry_month'       => 12,
                'expiry_year'        => 2099,
                'iin'                => 203040,
                'last4'              => '1212',
                'network'            => 'Bajaj Finserv',
                'type'               => 'credit',
                'issuer'             => null,
                'emi'                => 1,
                'vault'              => 'rzpvault',
                'vault_token'        => 'MjAzMDQwMDAwMDEyMTIxMg==',
                'global_fingerprint' => '==gMxITMyEDMwADMwQDMzAjM'
            ])->toArray();

        $payment = $this->fixtures->create(
            'payment',
            [
                'merchant_id'       => '10000000000000',
                'amount'            => 500000,
                'method'            => 'emi',
                'status'            => 'authorized',
                'amount_authorized' => 500000,
                'card_id'           => $card['id'],
                'emi_plan_id'       => '30111111111110',
                'emi_subvention'    => 'customer',
                'gateway'           => 'bajajfinserv',
                'terminal_id'       => 'AqdfGh5460opVt',
            ])->toArray();

        $this->fixtures->create(
            'mozart',
            [
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'amount'     => 500000,
                'gateway'    => 'bajajfinserv',
                'raw'        => json_encode([
                    'DealID'           => 'CS905114097404',
                    'Errordescription' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                    'OrderNo'          => '104',
                    'RequestID'        => 'RZP190219162906768',
                    'Responsecode'     => '0',
                    'received'         => true,
                    'status'           => 'created',
                ])
            ]);

        $this->fixtures->create(
            'transaction',
            [
                'entity_id'   => $payment['id'],
                'merchant_id' => '10000000000000',
            ]);

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'BajajFinserv');
    }

    protected function setBflPaymentArray()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;

        // Converting to a bajaj finserv card number
        $this->payment['card']['number'] = '2030400000121212';

        unset($this->payment['card']['cvv']);

        unset($this->payment['card']['expiry_month']);

        unset($this->payment['card']['expiry_year']);
    }
}
