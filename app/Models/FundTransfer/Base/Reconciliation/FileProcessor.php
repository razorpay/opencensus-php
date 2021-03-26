<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Settlement\SlackNotification;

abstract class FileProcessor extends Processor
{
    use Kotak\FileHandlerTrait;

    abstract protected function storeFile($reconcileFile);

    abstract protected function setDate($data);

    /**
     * Checks the reverse file extension is same as specified by the bank
     *
     * @param string $filePath
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getFileExtensionForParsing(string $filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($extension, static::$fileExtensions, true) === true)
        {
            return $extension;
        }

        throw new LogicException(
            "Extension not handled: {$extension}"
            , null
            , [
                'file_path' => $filePath
            ]);
    }

    /**
     * Gives list of extension which should be ignored while recon.
     * - Example: HDFC may give a .clt file in reverse folder.
     *            This file indicates that the file has been picked up for processing.
     *            This file doesn't contain any information apart from what we sent
     *
     * @return array
     */
    protected function getIgnoreExtensions(): array
    {
        return [];
    }

    protected function parseFile(string $filePath, string $extension)
    {
        switch ($extension)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                return $this->parseExcelSheets($filePath);

            case FileStore\Format::CSV:
                return $this->parseTextFile($filePath, ',');

            default:
                return $this->parseTextFile($filePath, static::$delimiter);
        }
    }

    protected function processReconciliation(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $reconcileFile = $this->getReconciliationFile($input);

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            [
                'recon_filename' => $reconcileFile,
                'input'          => $input
            ]);

        if ($reconcileFile === null)
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                ['message' => 'No file present']);

            return [];
        }

        $customProperties = [
            'channel'     => static::$channel,
            'file_name'   => basename($reconcileFile),
            ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::REVERSE_FEED_RECEIVED,
            null,
            null,
            $customProperties);

        $extension = $this->getFileExtensionForParsing($reconcileFile);

        // If file extension is a part of ignore list then file is ignored from parsing
        $ignoreExtensions = $this->getIgnoreExtensions();

        if (in_array($extension, $ignoreExtensions, true) === true)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_REVERSE_FILE_SKIPPED,
                [
                    'channel'   => static::$channel,
                    'extension' => $extension
                ]);

            return;
        }

        $data = $this->parseFile($reconcileFile, $extension);

        $this->storeReconciledFile($reconcileFile);

        $response = null;

        if (empty($data) === true)
        {
            $response = ['message' => 'no records to reconcile'];
        }
        else
        {
            $this->setDate($data);

            $response = $this->startReconciliation($data, basename($reconcileFile));
        }

        $this->sendEmail();

        // TODO: Need to dispatch FTA RECON JOB here as well (like we do for API based)

        return $response;
    }

    protected function getReconciliationFile($input)
    {
        $reconcileFile = null;

        try
        {
            if ((isset($input['source']) === true) and
                ($input['source'] === 'lambda'))
            {
                $key = urldecode($input['key']);

                $bucket = 'h2h_bucket';
                $region = null;

                // With Old lambda the bucket and region is not being sent
                // but with new lambda we are sending bucket and region
                // it is added to support both the lambdas
                // once we deprecate the old lambda then this can be removed from here
                // and can be added at the validation layer itself
                if(empty($input['bucket']) === false)
                {
                    $bucket = $input['bucket'];
                }

                if(empty($input['region']) === false)
                {
                    $region = $input['region'];
                }

                $reconcileFile = $this->getH2HFileFromAws($key, false, $bucket, $region);
            }
            else
            {
                $reconcileFile = $this->getFile($input);
            }
        }
        catch (\Throwable $exception)
        {
            (new SlackNotification)->send("Reverse feed download failed", $input, $exception);
        }

        return $reconcileFile;
    }

    protected function verifySettlements(array $input)
    {
        return;
    }

    // Adding this to increase the allowed system time limit of 60 sec
    // because the bank started sending the file of more than 2MB size recently
    // which started taking more than 60s and we are observing termination of the
    // request
    // ref: https://razorpay.slack.com/archives/CAW3Z5Y6P/p1615802018055000
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(240);
    }
}
