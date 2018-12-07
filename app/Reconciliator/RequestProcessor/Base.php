<?php

namespace RZP\Reconciliator\RequestProcessor;

use DirectoryIterator;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Base\Core;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Validator;
use RZP\Trace\TraceCode;

class Base extends Core
{
    const GATEWAY                 = 'gateway';
    const ATTACHMENT_COUNT        = 'attachment_count';
    const ATTACHMENT_HYPHEN_COUNT = 'attachment-count';
    const ATTACHMENT_HYPHEN_ONE   = 'attachment-1';
    const FORCE_UPDATE            = 'force_update';
    const FORCE_AUTHORIZE         = 'force_authorize';

    const SOURCE                  = 'source';

    /**
     * Type of request processor
     */
    const LAMBDA                  = 'lambda';
    const MAILGUN                 = 'mailgun';
    const MANUAL                  = 'manual';

    const FILE_DETAILS            = 'file_details';
    const INPUT_DETAILS           = 'input_details';

    /**
     * These field can be force updated with passed with request
     */
    const REFUND_ARN            = 'refund_arn';
    const PAYMENT_ARN           = 'payment_arn';
    const PAYMENT_AUTH_CODE     = 'payment_auth_code';

    /******************
     * Gateway constants
     ******************/

    const HDFC                   = 'HDFC';
    const AXIS                   = 'Axis';
    const KOTAK                  = 'Kotak';
    const AIRTEL                 = 'Airtel';
    const BILLDESK               = 'BillDesk';
    const PAYZAPP                = 'PayZapp';
    const MPESA                  = 'Mpesa';
    const MOBIKWIK               = 'Mobikwik';
    const AMAZONPAY              = 'Amazonpay';
    const PAYTM                  = 'Paytm';
    const OLAMONEY               = 'Olamoney';
    const FREECHARGE             = 'Freecharge';
    const EMANDATE_AXIS          = 'EmandateAxis';
    const NETBANKING_AXIS        = 'NetbankingAxis';
    const NETBANKING_ICICI       = 'NetbankingIcici';
    const NETBANKING_CANARA      = 'NetbankingCanara';
    const NETBANKING_FEDERAL     = 'NetbankingFederal';
    const NETBANKING_CORPORATION = 'NetbankingCorporation';
    const NETBANKING_RBL         = 'NetbankingRbl';
    const NETBANKING_CSB         = 'NetbankingCsb';
    const NETBANKING_IDFC        = 'NetbankingIdfc';
    const NETBANKING_INDUSIND    = 'NetbankingIndusind';
    const NETBANKING_PNB         = 'NetbankingPnb';
    const NETBANKING_BOB         = 'NetbankingBob';
    const NETBANKING_OBC         = 'NetbankingObc';
    const NETBANKING_EQUITAS     = 'NetbankingEquitas';
    const NETBANKING_HDFC        = 'NetbankingHdfc';
    const VIRTUAL_ACC_KOTAK      = 'VirtualAccKotak';
    const VIRTUAL_ACC_YESBANK    = 'VirtualAccYesBank';
    const JIOMONEY               = 'Jiomoney';
    const UPI_SBI                = 'UpiSbi';
    const PAYUMONEY              = 'PayuMoney';
    const EBS                    = 'Ebs';
    const FIRST_DATA             = 'FirstData';
    const UPI_ICICI              = 'UpiIcici';
    const ADMIN                  = 'admin';
    const HITACHI                = 'Hitachi';
    const CARD_FSS_HDFC          = 'CardFssHdfc';
    const CARD_FSS_BOB           = 'CardFssBob';
    const ATOM                   = 'Atom';
    const UPI_HDFC               = 'UpiHdfc';
    const UPI_HULK               = 'UpiHulk';
    const UPI_AXIS               = 'UpiAxis';
    const AMEX                   = 'Amex';

    /**
     * The gateway names should be the same name as the directories present under 'reconciliator'
     * The banks send their MIS files through this sender address.
     * List email addresses in lower case. Addresses are case insensitive, our checks are not.
     */
    const GATEWAY_SENDER_MAPPING = [
        self::HDFC                   => ['payoutreport@hdfcbank.com'],
        self::AXIS                   => ['pg.estatements@axisbank.com'],
        self::BILLDESK               => [],
        self::PAYZAPP                => ['donotreply@enstage.com'],
        self::MOBIKWIK               => [],
        self::AMAZONPAY              => [],
        self::MPESA                  => [],
        self::PAYTM                  => [],
        self::KOTAK                  => ['bankalerts@kotak.com'],
        self::OLAMONEY               => ['olamoney-noreply@olacabs.com'],
        self::FREECHARGE             => ['noreply@fcemail.in', 'noreply@freechargemail.in'],
        self::EMANDATE_AXIS          => ['cmsdirect.debit@axisbank.com'],
        self::NETBANKING_AXIS        => ['ibanking@axisbank.com'],
        self::NETBANKING_ICICI       => ['ubpshelp@icicibank.com'],
        self::NETBANKING_FEDERAL     => ['fednetrm@federalbank.co.in'],
        self::NETBANKING_RBL         => ['internetbanking@rblbank.com'],
        self::NETBANKING_EQUITAS     => [],
        self::NETBANKING_CANARA      => [],
        self::AIRTEL                 => ['no-reply@airtelbank.com'],
        self::NETBANKING_INDUSIND    => [],
        self::NETBANKING_OBC         => [],
        self::NETBANKING_PNB         => [],
        self::NETBANKING_IDFC        => [],
        self::NETBANKING_CSB         => ['noreply@csb.co.in'],
        self::NETBANKING_CORPORATION => ['ncbsfeba@corpbank.co.in'],
        self::NETBANKING_BOB         => ['billpay@bankofbaroda.com'],
        self::NETBANKING_HDFC        => [],
        self::JIOMONEY               => [],
        self::EBS                    => [],
        self::FIRST_DATA             => ['customer.care@icici.mailserv.in'],
        self::UPI_ICICI              => ['eazypay@icicibank.com'],
        self::VIRTUAL_ACC_KOTAK      => ['kmb.reports@kotak.com'],
        self::VIRTUAL_ACC_YESBANK    => ['ereport@yesbank.in'],
        self::UPI_SBI                => [],
        self::PAYUMONEY              => [],
        self::HITACHI                => ['reportsmailer@hitachi-payments.com'],
        self::CARD_FSS_HDFC          => ['merchantops@fss.co.in'],
        self::ATOM                   => [],
        self::CARD_FSS_BOB           => [],
        self::UPI_AXIS               => [],
        self::UPI_HDFC               => ['upi@hdfcbank.net'],
        self::UPI_HULK               => [],
        self::AMEX                   => [],

        // Used when someone from the team needs to send the
        // reconciliation file via mail for reconciliation.
        self::ADMIN                  => ['kajol.nigam@razorpay.com'],
    ];

    /**
     * Set of attributes, which act as configuration for recon processing
     * and can be optionally passed in the request.
     */
    const CONFIG_PARAMS = [
        self::FORCE_UPDATE,
        self::SOURCE,
        self::FORCE_AUTHORIZE
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

    public function getGatewayReconciliator()
    {
        return $this->gatewayReconciliator;
    }

    protected function setGatewayReconciliatorObject()
    {
        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' .
                                         $this->gateway . '\\' .
                                         'Reconciliate';

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName($this->gateway);
    }

    /**
     * @param   array        $inputDetails
     * @param   array        $input
     * @param   string       $fileLocationType
     *
     * @return array
     */
    protected function getFileDetailsFromInput(
        array $inputDetails,
        array $input,
        string $fileLocationType = FileProcessor::UPLOADED)
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
                    $this->handleZipProcessingException($ex, $zipFileDetails);
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
    protected function handleZipProcessingException(\Exception $ex, array $zipFileDetails)
    {
        $this->trace->traceException($ex);

        //
        // Axis sends hundreds of files daily with wrong password and one
        // file with the right password. We don't know which file has the
        // right password and which file has the wrong password.
        // Hence, we suppress all axis wrong password errors.
        //
        if (($this->gateway !== self::AXIS) and
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
     */
    protected function getFileDetailsFromZipFile(array $zipFileDetails)
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

            //
            // If not a file, check for directory.
            // isDot() returns true for the hidden default directories '.' and '..' , so we
            // have put a 'false' condition here as we want to go into actual directories only.
            //
            else if (($unzippedFile->isDir() === true) and ($unzippedFile->isDot() === false))
            {
                $dirFiles = $this->getFilesFromDirectory($unzippedFile);

                $allExtractedFilesDetails = array_merge($allExtractedFilesDetails, $dirFiles);
            }
        }

        return $allExtractedFilesDetails;
    }

    /**
     * Get all the files from this directory.
     * This does not go inside nested sub-directories.
     *
     * @param \SplFileInfo $dir
     * @return array List of files
     */
    protected function getFilesFromDirectory(\SplFileInfo $dir)
    {
        $dirFiles = [];

        $unzippedFiles = new DirectoryIterator($dir->getPathname());

        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $dirFiles[] = $this->fileProcessor
                    ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }
        }

        return $dirFiles;
    }

    /**
     * Get all the files from the directory recursively.
     * This goes inside nested sub-directories.
     *
     * @param \SplFileInfo $dir
     * @return array List of files
     */
    protected function getFilesFromDirectoryRecursively(\SplFileInfo $dir)
    {
        $dirFiles = [];

        $unzippedFiles = new DirectoryIterator($dir->getPathname());

        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $dirFiles[] = $this->fileProcessor
                    ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }

            //
            // If not a file, check for directory.
            // isDot() returns true for the hidden default directories '.' and '..' , so we
            // have put a 'false' condition here as we want to go into actual directories only.
            //
            else if (($unzippedFile->isDir() === true) and ($unzippedFile->isDot() === false))
            {
                $dirFiles = array_merge($dirFiles, $this->getFilesFromDirectoryRecursively($unzippedFile));
            }
        }

        return $dirFiles;
    }

    protected function getFileDetailsFromAllZipFiles(array $zipFileDetails)
    {
        $allExtractedFileDetails = [];

        foreach ($zipFileDetails as $zf)
        {
            try
            {
                $extractedFileDetails = $this->getFileDetailsFromZipFile($zf);
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
                        'zip_file_details'  => $zf,
                        'gateway'           => $this->gateway,
                    ]);

                continue;
            }

            $allExtractedFileDetails = array_merge($allExtractedFileDetails, $extractedFileDetails);
        }

        return $allExtractedFileDetails;
    }

    /**
     * Fetches the documents from the link, stores them in tmp
     * after extraction if necessary, deletes the zip file, keeping
     * the imp files
     *
     * @param  array $input
     * @return array
     */
    protected function fetchAndStoreLinkDocuments(array & $input)
    {
        if (empty($input[self::ATTACHMENT_HYPHEN_COUNT]) === true)
        {
            $input[self::ATTACHMENT_HYPHEN_COUNT] = 0;
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

        $attachmentCount = (string) ((int) $input[self::ATTACHMENT_HYPHEN_COUNT] + 1);

        $input['attachment-' . $attachmentCount] = $file;
        $input[self::ATTACHMENT_HYPHEN_COUNT] = $attachmentCount;
    }

    /**
     * Returns true only if all the files are zip files.
     * Returns false otherwise.
     *
     * @param  array    $extractedFileDetails
     * @return bool     true if all the files are zip files
     *                  false, otherwise.
     */
    protected function isTwoLevelZip(array $extractedFileDetails): bool
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
