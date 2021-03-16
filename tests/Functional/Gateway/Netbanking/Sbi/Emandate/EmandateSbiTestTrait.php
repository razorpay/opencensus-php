<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Sbi\EMandate;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Gateway\Netbanking\Sbi\Emandate\DebitFileHeadings;
use RZP\Gateway\Netbanking\Sbi\Emandate\RegisterFileHeadings;

trait EmandateSbiTestTrait
{
    protected function createRegistrationPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);

        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        return $this->getDbEntityById('payment', $response['razorpay_payment_id'])->toArray();
    }

    protected function createSecondReccuringPayment($token)
    {
        $paymentRequestArray = $this->payment;

        $paymentRequestArray[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);

        $paymentRequestArray['amount'] = 3000;

        $paymentRequestArray['order_id'] = $order->getPublicId();

        $response = $this->doS2SRecurringPayment($paymentRequestArray);

        return $this->getDbEntityById('payment', $response['razorpay_payment_id']);
    }

    protected function getRegisterSuccessExcel($entities)
    {
        $items = [];

        foreach ($entities as $index => $entity)
        {
            if ($entity['status'] === 'SUCCESS')
            {
                $amount = number_format($entity['payment']['amount'] / 100, '2', '.', '');

                $items[] = [
                    RegisterFileHeadings::SR_NO                => strval($index + 1),
                    RegisterFileHeadings::EMANDATE_TYPE        => 'random',
                    RegisterFileHeadings::UMRN                 => $entity['umrn'],
                    RegisterFileHeadings::MERCHANT_ID          => 'test ID',
                    RegisterFileHeadings::CUSTOMER_REF_NO      => $entity['payment']['id'],
                    RegisterFileHeadings::SCHEME_NAME          => 'test Scheme',
                    RegisterFileHeadings::SUB_SCHEME           => 'test subScheme',
                    RegisterFileHeadings::DEBIT_CUSTOMER_NAME  => 'Test Account',
                    RegisterFileHeadings::DEBIT_ACCOUNT_NUMBER => $entity['accNo'] ?? self::ACCOUNT_NUMBER,
                    RegisterFileHeadings::DEBIT_ACCOUNT_TYPE   => 'random',
                    RegisterFileHeadings::DEBIT_IFSC           => 'SBIN0000001',
                    RegisterFileHeadings::DEBIT_BANK_NAME      => 'SBI',
                    RegisterFileHeadings::AMOUNT               => $amount,
                    RegisterFileHeadings::AMOUNT_TYPE          => 'Max',
                    RegisterFileHeadings::CUSTOMER_ID          => '123456',
                    RegisterFileHeadings::PERIOD               => 'random',
                    RegisterFileHeadings::PAYMENT_TYPE         => 'ADHO',
                    RegisterFileHeadings::FREQUENCY            => 'ADHO',
                    RegisterFileHeadings::START_DATE           => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    RegisterFileHeadings::END_DATE             => Carbon::now(Timezone::IST)->addYears(10)->format('d/m/Y'),
                    RegisterFileHeadings::MOBILE               => '0000000000',
                    RegisterFileHeadings::EMAIL                => 'test@gmail.com',
                    RegisterFileHeadings::OTHER_REF_NO         => 'test',
                    RegisterFileHeadings::PAN_NUMBER           => '1234',
                    RegisterFileHeadings::AUTO_DEBIT_DATE      => '',
                    RegisterFileHeadings::AUTHENTICATION_MODE  => '',
                    RegisterFileHeadings::DATE_PROCESSED       => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    RegisterFileHeadings::STATUS               => $entity['status'],
                    RegisterFileHeadings::NO_OF_DAYS_PENDING   => '',
                    RegisterFileHeadings::REJECT_REASON        => $entity['return_reason'] ?? 'random reason',
                ];
            }
        }

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A6',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('Sbi Register Recon Emandate', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        return (new TestingFile('Sbi-Register-Recon-Emandate.xlsx', $handle));
    }

    protected function getRegisterFailureCsv($entities)
    {
        $data = [];

        foreach ($entities as $entity)
        {
            if ($entity['status'] === 'FAILURE')
            {
                $data[] = [
                    RegisterFileHeadings::TRANSACTION_DATE        => Carbon::now(Timezone::IST)->format('d/m/Y H:i:s'),
                    RegisterFileHeadings::CUSTOMER_NAME           => 'test',
                    RegisterFileHeadings::CUSTOMER_REF_NO         => $entity['payment']['id'],
                    RegisterFileHeadings::CUSTOMER_ACCOUNT_NUMBER => $entity['accNo'] ?? self::ACCOUNT_NUMBER,
                    RegisterFileHeadings::AMOUNT                  => '1.00',
                    RegisterFileHeadings::MAX_AMOUNT              => '99999.00',
                    RegisterFileHeadings::STATUS                  => $entity['status'],
                    RegisterFileHeadings::STATUS_DESCRIPTION      => $entity['return_reason'],
                    RegisterFileHeadings::START_DATE_REJECT_FILE  => '',
                    RegisterFileHeadings::END_DATE_REJECT_FILE    => '',
                    RegisterFileHeadings::FREQUENCY               => '',
                    RegisterFileHeadings::UMRN_REJECT_RILE        => $entity['umrn'],
                    RegisterFileHeadings::SBI_REFERENCE_NO        => $entity['umrn'],
                    RegisterFileHeadings::MODE_OF_VERIFICATION    => 'DB',
                    RegisterFileHeadings::AMOUNT_TYPE_REJECT_FILE => 'M',
                ];
            }
        }

        $txt = $this->generateTextWithHeadings($data, ',', false, array_keys(current($data)));

        $handle = tmpfile();

        fputs($handle, $txt);

        fseek($handle, 0);

        return (new TestingFile('Sbi-Register-Recon-Emandate.txt', $handle));
    }

    protected function uploadBatchFile($file, $type)
    {
        $url = '/admin/batches';

        $this->ba->adminAuth();

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'emandate',
                'sub_type' => $type,
                'gateway'  => 'sbi',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function generateDebitGatewayFile()
    {
        $this->ba->adminAuth();

        $testData = $this->testData['testEmandateDebit'];

        return $this->runRequestResponseFlow($testData);
    }

    protected function uploadDebitBatchFile($entities)
    {
        $items = [];

        foreach ($entities as $index => $entity)
        {
            if($entity['payment']['recurring_type'] === 'auto')
            {
                $amount = number_format($entity['payment']['amount'] / 100, '2', '.', '');

                $items[] = [
                    DebitFileHeadings::SERIAL_NUMBER             => $index + 1,
                    DebitFileHeadings::UMRN                      => '1234',
                    DebitFileHeadings::CUSTOMER_CODE             => 'test customer code',
                    DebitFileHeadings::CUSTOMER_NAME             => 'test customer name',
                    DebitFileHeadings::TRANSACTION_INPUT_CHANNEL => 'test channel',
                    DebitFileHeadings::FILE_NAME                 => 'Test file',
                    DebitFileHeadings::CUSTOMER_REF_NO           => $entity['payment']['id'],
                    DebitFileHeadings::MANDATE_HOLDER_NAME       => 'Test Account',
                    DebitFileHeadings::MANDATE_HOLDER_ACCOUNT_NO => $entity['AccNo'] ?? '12345678901234',
                    DebitFileHeadings::DEBIT_BANK_IFSC           => 'SBIN0000001',
                    DebitFileHeadings::DEBIT_DATE_RESP           => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    DebitFileHeadings::AMOUNT                    => $amount,
                    DebitFileHeadings::JOURNAL_NUMBER            => '',
                    DebitFileHeadings::PROCESSING_DATE           => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    DebitFileHeadings::DEBIT_STATUS              => $entity['status'],
                    DebitFileHeadings::CREDIT_STATUS             => '',
                    DebitFileHeadings::REASON                    => $entity['return_reason'] ?? 'random reason',
                    DebitFileHeadings::CREDIT_DATE               => Carbon::now(Timezone::IST)->format('d/m/Y'),
                ];
            }
        }

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A6',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('Sbi Debit Recon Emandate', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Sbi-Debit-Recon-Emandate.xlsx', $handle));

        return $this->uploadBatchFile($file, 'debit');
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
}
