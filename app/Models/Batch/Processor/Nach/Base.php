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
use RZP\Reconciliator\Base\Foundation\SubReconciliate;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Models\Feature;
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
        $merchant = $entity->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            return true;
        }

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
        $this->trace->info(TraceCode::NACH_DEBIT_RECONCILE_AT,
        [
            "payment_id" => $entity->getId(),
        ]);

        $time = Carbon::now(Timezone::IST)->getTimestamp();

        $data = [
            BaseReconciliate::RECONCILED_AT => $time,
        ];

        // Transaction is not updated for CLS MIDs because transaction does not exist in api hot storage and
        // the ART dual write updates will flow by the event streaming events to CLS Makeshift.
        //change here hotfix : fetch txn from tiDB & check ref3 enabled
        //adding one more check to check the merchant is CLS or not
        $merchant = $entity->merchant;

        $txn = $this->repo->transaction->findByEntityIdWithoutMerchantTidb($entity->getId());

        if (($txn !== null && $txn->getReference3() === "enabled") || $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            (new SubReconciliate())->sendPaymentReconNFCDataToCLS($entity, $data);

            return;
        }

        $transaction = $entity->transaction;

        if ($transaction->isReconciled() === true)
        {
            return;
        }

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
