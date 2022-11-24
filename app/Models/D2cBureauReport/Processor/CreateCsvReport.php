<?php

namespace RZP\Models\D2cBureauReport\Processor;

use App;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Services\UfhService;
use RZP\Models\FileStore\Utility;
use RZP\Models\D2cBureauReport\Entity;

class CreateCsvReport
{
    protected $fileId;

    protected $merchantId;

    protected $d2cReport;

    protected $app;

    /**
     * Repository manager
     *
     * @var \RZP\Base\RepositoryManager
     */
    protected $repo;

    protected $trace;

    public function __construct($report)
    {
        $this->app                  = App::getFacadeRoot();
        $this->trace                = $this->app['trace'];
        $this->d2cReport            = $report;
        $this->fileId               = $this->d2cReport['ufh_file_id'];
        $this->merchantId           = $this->d2cReport['merchant_id'];
        $this->repo                 = $this->app['repo'];
    }

    public function createCsvReport()
    {
        $ufhService = $this->app['ufh.service'];

        $jsonFileUrl = $ufhService->getSignedUrl($this->fileId, [], $this->merchantId);

        $fileContents = file_get_contents($jsonFileUrl['signed_url']);

        $csvFileId = $this->paresAndConvertToCsv($fileContents);

        $bureauReportId = $this->d2cReport->getPublicId();

        /** @var Entity $bureauReport */
        $bureauReport = $this->repo->d2c_bureau_report->findByPublicId($bureauReportId);

        $bureauReport->setCsvReportFileId($csvFileId);

        $this->repo->saveOrFail($bureauReport);

        return $bureauReport;
    }

    public function paresAndConvertToCsv(string $jsonFileContents)
    {
        $fileName = 'parsed_report_' . $this->d2cReport->getPublicId() . '.csv';

        $filePath = Utility::getStorageDir('files/d2c') . '/' . $fileName;

        $file = fopen($filePath, 'w');

        $columns = [
            'AccountHoldertypeCode',
            'Account_Number',
            'Account_Status',
            'Account_Type',
            'Amount_Past_Due',
            'Highest_Credit_or_Original_Loan_Amount',
            'CurrencyCode',
            'Current_Balance',
            'Credit_Limit_Amount',
            'CurrencyCode',
            'DateOfAddition',
            'Date_Closed',
            'Date_Reported',
            'Date_of_First_Delinquency',
            'DefaultStatusDate',
            'SuitFiledWillfulDefaultWrittenOffStatus',
            'SuitFiled_WilfulDefault',
            'Date_of_Last_Payment',
            'DefaultStatusDate',
            'Bureau History (in months)',
            'Asset_Classification',
            'Days_Past_Due',
            'Month',
            'Year',
        ];

        $accountHistoryColumns = [
            'Asset_Classification',
            'Days_Past_Due',
            'Month',
            'Year'
        ];

        fputcsv($file, $columns ,',', chr(127));

        $jsonMap = json_decode($jsonFileContents, true);

        $accountDetails = $jsonMap['INProfileResponse']['CAIS_Account']['CAIS_Account_DETAILS'];

        if (array_key_exists(0, $accountDetails) === true)
        {
            foreach ($accountDetails as $row)
            {
                $commonRow = [];

                for ($x = 0; $x < 19; $x++)
                {
                    $col = $columns[$x];

                    if (($col === 'DateOfAddition') or
                        ($col === 'Date_Closed') or
                        ($col === 'Date_Reported') or
                        ($col === 'Date_of_Last_Payment'))
                    {
                        if (array_key_exists($col, $row) === true)
                        {
                            array_push($commonRow, $this->getFormattedDate($row[$col]));
                        }
                        else
                        {
                                array_push($commonRow,'');
                        }
                        continue;
                    }
                    else if ($col === 'Account_Type')
                    {
                        if (array_key_exists($col, $row) === true)
                        {
                            array_push($commonRow, strval(AccountType::getAccountType($row[$col])));
                        }
                        else
                        {
                            array_push($commonRow,'');
                        }
                        continue;
                    }
                    else
                    {
                        if (array_key_exists($col,$row) === true)
                        {
                            array_push($commonRow ,$row[$col]);
                        }
                        else
                        {
                            array_push($commonRow, '');
                        }
                    }
                }

               if (array_key_exists('DateOfAddition', $row) === true)
               {
                   $d1 = date('Ymd');

                   $dateOfAddition = $row['DateOfAddition'];

                   $months = $this->getMonths($this->getFormattedDate($d1), $this->getFormattedDate($dateOfAddition));

                   array_push($commonRow, $months);
               }
               else
               {
                   array_push($commonRow,'');
               }

               if (array_key_exists(0, $row['CAIS_Account_History']) === true)
                {
                   for($y = 0; $y < count($row['CAIS_Account_History']); $y++)
                   {
                       $accountHistoryData = $row['CAIS_Account_History'][$y];

                        $oneRow = [];

                        foreach ($accountHistoryColumns as $accountHistoryColumn)
                        {
                            if (array_key_exists($accountHistoryColumn, $accountHistoryData) === true)
                            {
                                    array_push($oneRow, $accountHistoryData[$accountHistoryColumn]);
                            }
                            else
                            {
                                array_push($commonRow, '');
                            }
                        }
                        fputcsv($file, array_merge($commonRow, $oneRow), ',', chr(127));
                   }
               }
               else
               {
                   $accountHistoryData = $row['CAIS_Account_History'];

                   $oneRow = [];

                   foreach ($accountHistoryColumns as $accountHistoryColumn)
                   {
                        if (array_key_exists($accountHistoryColumn, $accountHistoryData) === true)
                        {
                           array_push($oneRow, $accountHistoryData[$accountHistoryColumn]);
                        }
                        else
                        {
                           array_push($commonRow, '');
                        }
                   }

                   fputcsv($file, array_merge($commonRow, $oneRow), ',', chr(127));
               }
            }
        }

        fclose($file);

        $csvFile = new UploadedFile($filePath, $fileName, 'text/csv', null, true);

        $ufhFile = $this->app['ufh.service']->uploadFileAndGetUrl($csvFile, $fileName, 'bureau_report_csv', $this->d2cReport);

        return $ufhFile[UfhService::FILE_ID];
    }

    public function getFormattedDate(string $date)
    {
        if (strlen($date) === 0)
        {
            return '';
        }

        return substr($date, 6, 2). '/' .substr($date, 4, 2). '/' .substr($date, 0, 4);
    }

    public function getMonths($toDate, $fromDate)
    {
        if ((strlen($toDate) === 0) or (strlen($fromDate) === 0))
        {
            return '';
        }

        $months = (12 * ((int) substr($toDate, 6, 10) - (int) substr($fromDate, 6, 10))) +
                ((int) substr($toDate, 3, 5) - (int) substr($fromDate, 3, 5));

        return (string) $months;
    }
}
