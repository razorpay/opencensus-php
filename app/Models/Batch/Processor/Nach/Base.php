<?php

namespace RZP\Models\Batch\Processor\Nach;

use Storage;
use ZipArchive;
use Carbon\Carbon;
use DirectoryIterator;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\FileProcessor;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

abstract class Base extends BaseProcessor
{
    protected function reconcileEntity($entity)
    {
        if ($this->shouldReconcileEntity($entity) === true)
        {
            try
            {
                $this->markEntityReconciled($entity);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::EMANDATE_RECON_ROW_FAILED,
                    [
                        'entity_id'  => $entity->getId(),
                    ]);
            }
        }
    }

    protected function shouldReconcileEntity($entity)
    {
        if ($entity->getTransactionId() === null)
        {
            $this->trace->critical(TraceCode::EMANDATE_RECON_ROW_FAILED, [
                'entity_id'  => $entity->getId(),
                'message'    => 'transaction missing for entity'
            ]);
            return false;
        }
        return true;
    }

    protected function markEntityReconciled($entity)
    {
        $transaction = $entity->transaction;

        if ($transaction->isReconciled() === true)
        {
            return;
        }

        $time = Carbon::now(Timezone::IST)->getTimestamp();

        $transaction->setReconciledAt($time);

        $this->repo->saveOrFail($transaction);
    }

    protected function parseFile(string $filePath): array
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($ext)
        {
            case FileStore\Format::ZIP:
                return $this->parseZipFile($filePath);
        }

        return parent::parseFile($filePath);
    }

    protected function parseZipFile($filePath): array
    {
        $files = [];

        $extractToPath = pathinfo(realpath($filePath), PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo(realpath($filePath), PATHINFO_FILENAME);

        $zip = new ZipArchive;

        $zipped = $zip->open($filePath);

        // Checking if it is actually a zipped file.
        if ($zipped === false)
        {
            throw new Exception\LogicException('Attempt to unzip a non-zip file:' .  $filePath);
        }

        $extracted = $zip->extractTo($extractToPath);

        // Checking if it has been successfully extracted
        if ($extracted === true)
        {
            $zip->close();
        }
        else
        {
            (new FileProcessor)->deleteDirectoryLocally($extractToPath);

            throw new Exception\LogicException('Failed to unzip file: ' .  $filePath);
        }

        $unzippedFiles = new DirectoryIterator($extractToPath);

        foreach ($unzippedFiles as $unzippedFile)
        {
            if (($unzippedFile->isDir() === true) and ($unzippedFile->isDot() === false))
            {
                $responseXmls = new DirectoryIterator($unzippedFile->getPathname());

                foreach ($responseXmls as $responseXml)
                {
                    if (($responseXml->isFile() === true) and ($responseXml->getExtension() === FileStore\Format::XML))
                    {
                        $files[] = ['xml' => $responseXml->getRealPath()];
                    }
                }
            }
            elseif (($unzippedFile->isFile() === true) and ($unzippedFile->getExtension() === FileStore\Format::XML))
            {
                $files[] = ['xml' => $unzippedFile->getRealPath()];
            }
        }

        return $files;
    }
}
