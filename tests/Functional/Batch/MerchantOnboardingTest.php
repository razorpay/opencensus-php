<?php

namespace RZP\Tests\Functional\Batch;

use Mail;

use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\MerchantOnboarding as MerchantOnboardingMail;

class MerchantOnboardingTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testSbiEmi()
    {
        Mail::fake();

        $sampleMerchantId = '7thBRSDf3F7NHL';

        $entries = [
            [
                Header::MERCHANT_ONBOARDING_EMI_SBI_MID         => $sampleMerchantId,
                Header::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_MID => '250000185',
                Header::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_TID => '38R00001',
            ],
        ];

        $url = $this->writeToCsvFile($entries, 'file', null, 'files/batch');

        $uploadedFile = $this->createUploadedFileCsv($url);

        $this->fixtures->merchant->createAccount('7thBRSDf3F7NHL');

        $content = [
            'type' => 'merchant_onboarding',
            'gateway' => 'emi_sbi',
        ];

        $this->makeBatchRequest($content, $uploadedFile);

        $batch = $this->getDbLastEntityToArray('batch');
        $this->assertArraySelectiveEquals(
            [
                'type'            => 'merchant_onboarding',
                'gateway'         => 'emi_sbi',
                'status'          => 'processed',
                'processed_count' => 1,
            ],
            $batch
        );

        $terminal = $this->getDbLastEntityToArray('terminal');
        $this->assertArraySelectiveEquals(
            [
                'merchant_id'         => $sampleMerchantId,
                'gateway'             => 'emi_sbi',
                'gateway_merchant_id' => '250000185',
                'gateway_terminal_id' => '38R00001',
                'enabled'             => true,
            ],
            $terminal
        );

        Mail::assertSent(
            MerchantOnboardingMail::class,
            function ($mail) {
                $this->assertNotEmpty($mail->attachments);

                $subject = 'Razorpay | Merchant onboarding file for SBI EMI dated';
                $this->assertStringStartsWith($subject, $mail->subject);

                return true;
            }
        );
    }

    protected function makeBatchRequest($content, $file)
    {
        $request = [
            'url' => '/admin/batches',
            'method' => 'POST',
            'content' => $content,
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
