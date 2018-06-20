<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;

use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Kotak;

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
        $reconcileFile = $this->getReconcilationFile($input);

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

            $response = $this->startReconciliation($data);
        }

        $this->sendEmail();

        return $response;
    }

    protected function getReconcilationFile($input)
    {
        $reconcileFile = null;

        if ((isset($input['source']) === true) and
            ($input['source'] === 'lambda'))
        {
            $key = urldecode($input['key']);

            $reconcileFile = $this->getH2HFileFromAws($key);
        }
        else
        {
            $reconcileFile = $this->getFile($input);
        }

        return $reconcileFile;
    }
}
