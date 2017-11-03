<?php

namespace RZP\Reconciliator\RequestProcessor;

use DirectoryIterator;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Validator;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\FileProcessor;

class Base extends Core
{
    const GATEWAY          = 'gateway';

    const ATTACHMENT_COUNT = 'attachment_count';

     /**
     * The gateway names should be the same name as the directories present under 'reconciliator'
     * The banks send their MIS files through this sender address.
     * List email addresses in lower case. Addresses are case insensitive, our checks are not.
     */
    const GATEWAY_SENDER_MAPPING = [
        Orchestrator::HDFC                => ['payoutreport@hdfcbank.com'],
        Orchestrator::AXIS                => ['pg.estatements@axisbank.com'],
        Orchestrator::BILLDESK            => [],
        Orchestrator::PAYZAPP             => [],
        Orchestrator::MOBIKWIK            => [],
        Orchestrator::PAYTM               => [],
        Orchestrator::KOTAK               => ['bankalerts@kotak.com'],
        Orchestrator::OLAMONEY            => ['olamoney-noreply@olacabs.com'],
        Orchestrator::FREECHARGE          => ['noreply@freechargemail.in'],
        Orchestrator::NETBANKING_AXIS     => ['it.rico@axisbank.com'],
        Orchestrator::NETBANKING_ICICI    => ['ubpshelp@icicibank.com'],
        Orchestrator::NETBANKING_FEDERAL  => ['fednetrm@federalbank.co.in'],
        Orchestrator::NETBANKING_RBL      => ['internetbanking@rblbank.com'],
        Orchestrator::NETBANKING_INDUSIND => [],
        Orchestrator::NETBANKING_PNB      => [],
        Orchestrator::JIOMONEY            => [],
        Orchestrator::EBS                 => [],
        Orchestrator::FIRST_DATA          => ['customer.care@icici.mailserv.in'],
        Orchestrator::VIRTUAL_ACC_KOTAK   => ['kmb.reports@kotak.com'],
        // Used when someone from the team needs to send the
        // reconciliation file via mail for reconciliation.
        Orchestrator::ADMIN               => ['saurav.chowdhury@razorpay.com'],
    ];

    protected $validator;

    protected $fileProcessor;

    protected $gateway;

    protected $messenger;

    protected $gatewayReconciliator;

    public function __construct()
    {
        parent::__construct();

        $this->validator     = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->messenger     = new Messenger;
    }

    public function getGateway()
    {
        return $this->gateway;
    }

    protected function setGatewayReconciliatorObject()
    {
        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' .
            $this->gateway . '\\' . 'Reconciliate';

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }

    /**
     * @param        $inputDetails
     * @param        $input
     * @param string $fileLocationType
     *
     * @return array
     */
    protected function getFileDetailsFromInput(
        $inputDetails,
        $input,
        $fileLocationType = FileProcessor::UPLOADED)
    {
        $allFilesDetails = [];

        // Goes through each file and gets the file details.
        foreach (range(1, $inputDetails[self::ATTACHMENT_COUNT]) as $attachmentNumber)
        {
            // All the attachment files have to be named as 'attachment-{number}'
            // Validations should take care of this.
            $file = $input['attachment-' . $attachmentNumber];

            if ($this->fileProcessor->isZipFile($file, $fileLocationType) === true)
            {
                $zipFileDetails = [];

                try
                {
                    // Gets the actual zip file's details first.
                    $zipFileDetails = $this->fileProcessor->getFileDetails($file, $fileLocationType);

                    // Gets all files details present in the zip file.
                    $extractedFileDetails = $this->getFileDetailsFromZipFile($zipFileDetails);

                    // Throw an error if there's not even one file in the zip. Ideally, shouldn't happen.
                    if (empty($extractedFileDetails) === true)
                    {
                        // Exception instead of alert, to handle zip extraction exceptions also in the
                        // same alert in the catch block. (Cleaner code).
                        throw new Exception\ReconciliationException(
                            'No files present in the zip file attachment.',
                            ['file_name' => $file->getClientOriginalName()]
                        );
                    }

                    // Checks whether all the extracted files are zips too.
                    $multiLevelZip = $this->isTwoLevelZip($extractedFileDetails);

                    if ($multiLevelZip === true)
                    {
                        $extractedFileDetails = $this->getFileDetailsFromAllZipFiles($extractedFileDetails);
                    }

                    // Using array merge since $extractedFileDetails contains an
                    // array of file details of different files in the zip file.
                    $allFilesDetails = array_merge($allFilesDetails, $extractedFileDetails);
                }
                catch (\Exception $ex)
                {
                    $this->handleZipProcessingException($ex);
                }
            }
            else
            {
                // Except zip, all other file types will return with a single element
                // and not an array. Hence using push here instead of merge.
                $allFilesDetails[] = $this->fileProcessor->getFileDetails($file, $fileLocationType);
            }
        }

        return $allFilesDetails;
    }

    /**
     * @param  \Exception $ex
     */
    protected function handleZipProcessingException(\Exception $ex)
    {
        $this->trace->traceException($ex);

        //
        // Axis sends hundreds of files daily with wrong password and one
        // file with the right password. We don't know which file has the
        // right password and which file has the wrong password.
        // Hence, we suppress all axis wrong password errors.
        //
        if (($this->gateway !== Orchestrator::AXIS) and
            (str_contains($ex->getMessage(), 'Wrong password')))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because unzip file caused an exception -> ' .
                        $ex->getMessage(),
                    'file_details' => !empty($extractedFileDetails) ? $extractedFileDetails : null,
                    'gateway'      => $this->gateway,
                ]);
        }

        $this->deleteFileLocallyIfPresent($zipFileDetails);
    }

    /**
     * Unzips the zip file. Iterates through each extracted file and collects
     * the file details.
     *
     * @param array $zipFileDetails Zip file that needs to be extracted.
     * @return array File details of all the files present in the zip file.
     * @throws Exception\ReconciliationException
     */
    protected function getFileDetailsFromZipFile($zipFileDetails)
    {
        $allExtractedFilesDetails = [];

        $zipPassword = $this->gatewayReconciliator->getReconPassword($zipFileDetails);

        $use7z = $this->gatewayReconciliator->shouldUse7z($zipFileDetails);

        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zipFileDetails, $zipPassword, $use7z);

        $unzippedFiles = new DirectoryIterator($unzippedFolderPath);

        // Iterates through each zip file and gets the file details for them.
        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $allExtractedFilesDetails[] = $this->fileProcessor
                                                   ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }
        }

        return $allExtractedFilesDetails;
    }

    protected function getFileDetailsFromAllZipFiles($zipFilesDetails)
    {
        $allExtractedFileDetails = [];

        foreach ($zipFilesDetails as $zipFileDetails)
        {
            try
            {
                $extractedFileDetails = $this->getFileDetailsFromZipFile($zipFileDetails);
            }
            catch (\Exception $ex)
            {
                $level = Trace::ERROR;

                if ($this->gateway === self::AXIS)
                {
                    $level = Trace::INFO;
                }

                $this->trace->traceException(
                    $ex,
                    $level,
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'message'           => 'Unable to extract zip file',
                        'zip_file_details'  => $zipFileDetails,
                        'gateway'           => $this->gateway,
                    ]);

                continue;
            }

            $allExtractedFileDetails = array_merge($allExtractedFileDetails, $extractedFileDetails);
        }

        return $allExtractedFileDetails;
    }

    protected function fetchAndStoreLinkDocuments(array & $input)
    {
        if (empty($input['attachment-count']) === true)
        {
            $input['attachment-count'] = 0;
        }

        $link = $this->gatewayReconciliator->getSettlementFileLink($input['body-html']);

        $this->trace->info(
            TraceCode::RECON_FILE_LINK,
            [
                'link'    => $link,
                'gateway' => $this->gateway,
            ]);

        if ($link === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_LINK_NOT_FOUND,
                    'message'      => 'Unable to get the link for the MIS file',
                    'gateway'      => $this->gateway,
                ]);

            throw new Exception\ReconciliationException(
                'Unable to get the link',
                [
                    'gateway' => $this->gateway
                ]);
        }

        $file = $this->fileProcessor->getAndStoreFileFromLink($link);

        $attachmentCount = (string) ((int) $input['attachment-count'] + 1);

        $input['attachment-' . $attachmentCount] = $file;
        $input['attachment-count'] = $attachmentCount;
    }

    /**
     * Returns true only if all the files are zip files.
     * Returns false otherwise.
     *
     * @param $extractedFileDetails
     * @return true if all the files are zip files
     *         false, otherwise.
     */
    protected function isTwoLevelZip($extractedFileDetails)
    {
        foreach ($extractedFileDetails as $efd)
        {
            if ($efd[FileProcessor::EXTENSION] !== FileProcessor::ZIP_EXTENSION)
            {
                return false;
            }
        }

        return true;
    }

    protected function deleteFileLocallyIfPresent(array $fileDetails)
    {
        if (isset($fileDetails[FileProcessor::FILE_PATH]) === false)
        {
            return;
        }

        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
    }
}
