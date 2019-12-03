<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\BajajFinserv;

use RZP\Models\Batch\Status;
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
        parent::setUp();

        $this->gateway = 'bajajfinserv';

        $this->setMockGatewayTrue();

        $this->ba->publicAuth();

        $this->setBflPaymentArray();

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
        $card = $this->createCardEntity();

        // Payment marked as success in db and in recon
        $payment_success = $this->createPaymentEntities($card);

        // Payment marked as failure in db, but moved to auth from recon
        $payment_late_auth = $this->createPaymentEntities($card, 'failed');

         // Payment marked as failure in db and missing in recon file
        $payment_failure = $this->createPaymentEntities($card, 'failed');

        $this->mockReconContentFunction(function (& $content) use ($payment_failure)
        {
            if ($content['Dealer ID'] === $payment_failure['id'])
            {
                $content = [];
            }
        });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'BajajFinserv');

        $this->paymentSuccessAsserts($payment_success);

        $this->paymentSuccessAsserts($payment_late_auth);

        $this->paymentFailureAsserts($payment_failure);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'BajajFinserv',
                'status'          => Status::PROCESSED,
                'total_count'     => 2,
                'success_count'   => 2,
                'processed_count' => 2,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    protected function createCardEntity()
    {
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

        return $card;
    }

    protected function createPaymentEntities($card, $status = 'authorized')
    {
        $payment = $this->fixtures->create(
            'payment',
            [
                'merchant_id'       => '10000000000000',
                'amount'            => 500000,
                'method'            => 'emi',
                'status'            => $status,
                'amount_authorized' => 500000,
                'card_id'           => $card['id'],
                'emi_plan_id'       => '30111111111110',
                'emi_subvention'    => 'customer',
                'gateway'           => 'bajajfinserv',
                'terminal_id'       => 'AqdfGh5460opVt',
            ])->toArray();

        if (($status === 'authorized') or ($status === 'captured'))
        {
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

            $transaction = $this->fixtures->create(
                'transaction',
                [
                    'entity_id'   => $payment['id'],
                    'merchant_id' => '10000000000000',
                ])->toArray();

            $this->fixtures->edit(
                'payment',
                $payment['id'],
                [
                    'transaction_id' => $transaction['id'],
                ]);
        }
        else
        {
            $this->fixtures->create(
                'mozart',
                [
                    'payment_id' => $payment['id'],
                    'action'     => 'authorize',
                    'amount'     => 500000,
                    'gateway'    => 'bajajfinserv',
                    'raw'        => json_encode(
                        [
                            "Key"              => "4962575618567464",
                            "reqid"            => "RZP251119085000847",
                            "DealID"           => "",
                            "rqtype"           => "AUTH",
                            "status"           => "verification_failed",
                            "valkey"           => "4962575618567464",
                            "OrderNo"          => "DivEQedPoBab3x",
                            "enqinfo"          => [
                                [
                                    "Key"              => "4962575618567464",
                                    "DEALID"           => null,
                                    "ORDERNO"          => "DivEQedPoBab3x",
                                    "REQUESTID"        => "RZP211119083050976",
                                    "RESPONSECODE"     => "36",
                                    "ERRORDESCRIPTION" => "AMOUNT FINANCED SHOULD BE LESS THAN OR EQUAL TO THE MAXIMUM AMOUNT FINANCED AMOUNT AT SCHEME LEVEL"
                                ]
                            ],
                            "errdesc"          => "AMOUNT FINANCED SHOULD BE LESS THAN OR EQUAL TO THE MAXIMUM AMOUNT FINANCED AMOUNT AT SCHEME LEVEL",
                            "rescode"          => "36",
                            "MobileNo"         => "6953",
                            "received"         => true,
                            "RequestID"        => "RZP211119083050976",
                            "requeryid"        => "RZP211119083050976",
                            "Responsecode"     => "36",
                            "Errordescription" => "AMOUNT FINANCED SHOULD BE LESS THAN OR EQUAL TO THE MAXIMUM AMOUNT FINANCED AMOUNT AT SCHEME LEVEL"
                        ]
                    )
                ]);
        }

        return $payment;
    }

    protected function paymentSuccessAsserts(array $payment)
    {
        $payment = $this->getDbEntity('payment', ['id' => $payment['id']])->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status'     => 'authorized',
                'reference1' => '911082787695',
            ],
            $payment
        );

        $gatewayEntity = $this->getDbEntity('mozart', ['payment_id' => $payment['id']]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['DealID']);

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function paymentFailureAsserts(array $payment)
    {
        $gatewayEntity = $this->getDbEntity('mozart', ['payment_id' => $payment['id']]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEmpty($data['DealID']);

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payment['id']]);

        $this->assertNull($transactionEntity);
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
