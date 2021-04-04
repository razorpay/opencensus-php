<?php

namespace RZP\Tests\Functional\Gateway\File;

use Queue;

use RZP\Services\Mock\BeamService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Options as TerminalOptions;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FirstDataGatewayFileTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FirstDataGatewayFileTestData.php';

        parent::setUp();

        $rules = [
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'first_data',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'group'         => 'authentication',
                'auth_type'     => '3ds',
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
            [
                'method'        => 'card',
                'merchant_id'   => '100000Razorpay',
                'gateway'       => 'first_data',
                'type'          => 'sorter',
                'load'          => 10000,
                'auth_type'     => '3ds',
                'step'          => 'authentication',
                'authentication_gateway' => 'mpi_blade',
            ],
        ];

        foreach ($rules as $rule)
        {
            $this->fixtures->create('gateway_rule', $rule);
        }

        TerminalOptions::setTestChance(1000);

        $this->otpFlow = false;

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();

        $this->mockOtpElf();

        $this->terminal = $this->fixtures->create('terminal:shared_first_data_terminal');
    }

    public function testFile()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['preferred_auth'] = ['3ds'];

        $this->doAuthPayment($payment);
        $this->doAuthPayment($payment);

        $this->fixtures->edit('terminal', $this->terminal['id'], [ // 2nd file should get created for this terminal's payments.
            'gateway_merchant_id'   => '3387014020',
        ]);

        $this->doAuthPayment($payment);
        // not enrolled card. no pares data would've been cached for this
        $payment['card']['number'] = '4160210902353047';
        $payment['preferred_auth'] = ['3ds'];
        $this->doAuthPayment($payment);

        $this->ba->adminAuth();

        $beamServiceMock = $this->getMockBuilder(BeamService::class)
                                ->setConstructorArgs([$this->app])
                                ->setMethods(['beamPush'])
                                ->getMock();

        $beamServiceMock->method('beamPush')
                        ->will($this->returnCallback(
                            function ($pushData, $intervalInfo, $mailInfo, $synchronous)
                            {
                                $this->assertEquals('firstdata_pares_data_push', $pushData['job_name']);

                                $this->assertEquals(2, count($pushData['files']));

                                return [
                                    'failed' => null,
                                    'success' => $pushData['files'],
                                ];
                            }));

        $this->app['beam']->setMockService($beamServiceMock);

        $this->startTest();

        $files = $this->getEntities('file_store', [], true);

        $paymentsInFile = 0;

        foreach ($files['items'] as $file)
        {
            $fileContent = file_get_contents('storage/files/filestore/' . $file['location']);

            $rows = explode("\n", $fileContent);

            $this->assertEquals($rows[0], 'MID|OrderId|PaRes');

            foreach ($rows as $line)
            {
                if(empty($line) === true)
                {
                    // last newline case. first data does not care about this.
                    continue;
                }
                $parts = explode('|', $line);

                $this->assertEquals(3, count($parts));

                if ($parts[0] === 'MID')
                {
                    continue;
                }

                $this->assertEquals(10, strlen($parts[0])); //store id
                $this->assertEquals(14, strlen($parts[1])); //payment id
            }

            $paymentsInFile += (count($rows) - 2); // remove 1st line(header) & last newline
        }

        $this->assertEquals(3, $paymentsInFile);
    }
}
