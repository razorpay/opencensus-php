<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;

class Bdbl extends Base
{
    const BANK_NAME = 'Bdbl';
    const FILE_TYPE = FileStore\Type::BDBL_NETBANKING_COMBINED;
    const EXTENSION = FileStore\Format::XLS;
    const FILE_NAME = 'SUMMARY_FILE';

    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
        ];

        $refundsFile = $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::BDBL_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);
        }

        $count['total']  = $count['refunds'] + $count['claims'];
        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total']   = number_format($amount['total'] / 100, 2, '.', '');
        $amount['refunds'] = number_format($amount['refunds'] / 100, 2, '.', '');
        $amount['claims']  = number_format($amount['claims'] / 100, 2, '.', '');

        $date = Carbon::yesterday(Timezone::IST)->format('d.m.Y');

        $fromDate = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('d.m.Y');
        $toDate = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->format('d.m.Y');

        $this->createCombinedFile($data, $count, $amount);

        $summaryFile = $this->getFileData(FileStore\Type::BDBL_NETBANKING_COMBINED);

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
            'bankName'      => 'Axis Bank Ltd',
        ];

        $emailIds = [
            'recon'         => 'finances.recon@razorpay.com, amit.mohanty@razorpay.com',
            'l1'            => 'settlements@razorpay.com',
            'l2'            => 'chandrababu.g@razorpay.com',
            'transaction'   => 'support@razorpay.com'
        ];

        return [
            'bankName'      => self::BANK_NAME,
            'amount'        => $amount,
            'count'         => $count,
            'refundsFile'   => $refundsFile,
            'summaryFile'   => $summaryFile,
            'date'          => $date,
            'from'          => $fromDate,
            'to'            => $toDate,
            'account'       => $account,
            'rzpEmailId'    => $emailIds,
            'emails'        => $this->gatewayFile->getRecipients(),
        ];
    }

    protected function createCombinedFile($data,$count, $amount)
    {
        try
        {
            $fileData = $this->formatDataForFile($data, $count, $amount);

            $creator = new FileStore\Creator;

            $creator->extension(self::EXTENSION)
                ->content($fileData)
                ->name(self::FILE_NAME)
                ->store(FileStore\Store::S3)
                ->type(self::FILE_TYPE)
                ->entity($this->gatewayFile)
                ->headers(false)
                ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile($data, $count, $amount)
    {

        $refundArr = [];
        $refundArr['count'] = [];

        $claimArr = [];
        $claimArr['count'] = [];


        if (isset($data['refunds']) === true)
        {

            foreach ($data['refunds'] as $refunds)
            {
                $refunddate = Carbon::createFromTimestamp($refunds['refund']['created_at'], Timezone::IST)->format('d.m.Y');
                if(isset($refundArr['count'][$refunddate]))
                {
                    $refundArr['count'][$refunddate]+=1;
                    $refundArr['amount'][$refunddate]+= $refunds['refund']['amount'];
                }
                else
                {
                    $refundArr['count'][$refunddate] = 1;
                    $refundArr['amount'][$refunddate]= $refunds['refund']['amount'];
                }

            }
        }

        if (isset($data['claims']) === true)
        {
            foreach ($data['claims'] as $claim)
            {
                $claimdate = Carbon::createFromTimestamp($claim['payment']->getCreatedAt(), Timezone::IST)->format('d.m.Y');
                if(isset($claimArr['count'][$claimdate]))
                {
                    $claimArr['count'][$claimdate]+=1;
                    $claimArr['amount'][$claimdate]+= $claim['payment']->getAmount();
                }
                else
                {
                    $claimArr['count'][$claimdate] = 1;
                    $claimArr['amount'][$claimdate] = $claim['payment']->getAmount();
                }

            }
        }

        $dateofprocessing = Carbon::now(Timezone::IST)->format('d.m.Y');
        $fromDate = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('d.m.Y');
        $toDate = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->format('d.m.Y');

        $formattedData[] = [
            'col1' => '',
            'col2' => '',
            'col3' => 'Summary Report',
            'col4' => 'of',
            'col5' => 'Bandhan Bank',
            'col6' => ' ',
            'col7' => '  ',
        ];

        $formattedData[] = [
            'col1' => '      ',
            'col2' => '      ',
            'col3' => '      ',
            'col4' => '      ',
            'col5' => '      ',
            'col6' => '      ',
            'col7' => '      ',
        ];

        $formattedData[] = [
            'col1' => 'Date of Processing',
            'col2' => $dateofprocessing,
            'col3' => '      ',
            'col4' => '      ',
            'col5' => '      ',
            'col6' => '      ',
            'col7' => '      ',
        ];

        $formattedData[] = [
            'col1' => 'From :',
            'col2' => $fromDate,
            'col3' => '',
            'col4' => '',
            'col5' => '',
            'col6' => '',
            'col7' => '',
        ];

        $formattedData[] = [
            'col1' => 'To :',
            'col2' => $toDate,
            'col3' => '',
            'col4' => '',
            'col5' => '',
            'col6' => '',
            'col7' => '',
        ];

        $formattedData[] = [
            'col1' => '      ',
            'col2' => '      ',
            'col3' => '      ',
            'col4' => '      ',
            'col5' => '      ',
            'col6' => '      ',
            'col7' => '      ',
        ];

        $formattedData[] = [
            'col1' => '      ',
            'col2' => '      ',
            'col3' => '      ',
            'col4' => '      ',
            'col5' => '      ',
            'col6' => '      ',
            'col7' => '      ',
        ];

        $formattedData[] = [
            'col1' => ' ',
            'col2' => ' ',
            'col3' => '',
            'col3' => '',
            'col4' => '',
            'col5' => '',
            'col6' => 'Net Payable',
            'col7' => $amount['total'],
        ];

        $formattedData[] = [
            'col1' => '      ',
            'col2' => '      ',
            'col3' => '      ',
            'col4' => '      ',
            'col5' => '      ',
            'col6' => '      ',
            'col7' => '      ',
        ];

        $formattedData[] = [
            'col1' => '      ',
            'col2' => 'Transaction Details',
            'col3' => '      ',
            'col4' => '      ',
            'col5' => 'Refund Details',
            'col6' => '      ',
            'col7' => '      ',
        ];

        $formattedData[] = [
            'col1' => 'Date',
            'col2' => 'Count',
            'col3' => 'Amount',
            'col4' => 'Count',
            'col5' => 'Amount',
            'col6' => 'Net',
            'col7' => '      ',
        ];


        $output = array_merge($claimArr['count'],$refundArr['count']);

        foreach ($output as $d => $c){
            $claimcount = $claimArr['count'][$d]??0;
            $claimamount = $claimArr['amount'][$d]??0;
            $claimamountinrupee = number_format($claimamount / 100, 2, '.', '');

            $refundcount = $refundArr['count'][$d] ?? 0;
            $refundamount = $refundArr['amount'][$d] ?? 0;
            $refundamountinrupee = number_format($refundamount / 100, 2, '.', '');

            $nettotal = $claimamount - $refundamount;
            $nettotal = number_format($nettotal / 100, 2, '.', '');


            $formattedData[] = [
                'col1' => $d,
                'col2' => $claimcount,
                'col3' => $claimamountinrupee,
                'col4' => $refundcount,
                'col5' => $refundamountinrupee,
                'col6' => $nettotal,
                'col7' => '      ',
            ];
        }

        $formattedData[] = [
            'col1' => 'total',
            'col2' => $count['claims'],
            'col3' => $amount['claims'],
            'col4' => $count['refunds'],
            'col5' => $amount['refunds'],
            'col6' => $amount['total'],
            'col7' => '      ',
        ];


        return $formattedData;
    }
}
