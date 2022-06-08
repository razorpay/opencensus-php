<?php

namespace RZP\Models\FileStore;

use RZP\Jobs\InvoiceBucketUpdater;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * Fetches signed url for file assosciated with any entity
     *
     * @param $entity       string
     * @param $entityId     string
     * @return $signedUrl   string
     */
    public function signedUrlForEntityFile(string $entity, string $entityId)
    {
        (new Validator)->validateInput('entity_fetch', ['entity' => $entity, 'entity_id' => $entityId]);

        $entityObj = $this->repo->$entity->findByIdAndMerchantId($entityId, $this->merchant->getId());

        $file = $entityObj->file;

        $signedUrl = (new Accessor)->getSignedUrlOfFile($file);

        $data = [
            'file_id'    => $file->getId(),
            'signed_url' => $signedUrl,
        ];

        return $data;
    }

    public function getSignedUrl(string $fileStoreId, string $merchantId)
    {
        $signedUrls = (new Accessor)->id($fileStoreId)
                               ->merchantId($merchantId)
                               ->getSignedUrl();

        return $signedUrls[$fileStoreId];
    }

    public function migrateInvoiceBuckets(array $input)
    {
        if(empty($input['invoice_ids']) === false || empty($input['merchant_ids']) === false)
        {
            if(empty($input['merchant_ids']) === false)
            {
                $invoiceIds = $this->repo->commission_invoice->fetchInvoiceIdsFromMerchantsIds($input['merchant_ids']);
            }
            else
            {
                $invoiceIds = $input['invoice_ids'];
            }

            $this->trace->info(TraceCode::COMMISSION_INVOICES_TO_BE_MIGRATED,[
                'invoice_ids' => $invoiceIds,
            ]);

            return $this->migrateInvoiceBucketConfig($invoiceIds, $input['bucket_config']);
        }

        else
        {
            $total_count = $input['total_count'] ?? null;
            return $this->migrateInvoiceBucketConfigInBulk($input['bucket_config'], $total_count);
        }
    }

    protected function migrateInvoiceBucketConfig(array $invoiceIds, array $bucketConfig)
    {
        $fileStoreIds = $this->repo->file_store->getFileStoreIdWithEntiyId($invoiceIds);

        InvoiceBucketUpdater::dispatch($this->mode, $fileStoreIds, $bucketConfig);

        return [];
    }

    protected function migrateInvoiceBucketConfigInBulk(array $bucketConfig, int $total_count = null)
    {
        $limit =500;

        $afterId = null;

        $count = 0;

        while (true)
        {
            if(is_null($total_count)===false)
            {
                $limit = min($limit, $total_count - $count);

                 if($limit<=0) {
                     break;
                 }
            }

            $invoiceIds = $this->repo->commission_invoice->fetchInvoiceIds($limit, $afterId);

            if (empty($invoiceIds) === true) {
                break;
            }

            $afterId = end($invoiceIds);

            $count += count($invoiceIds);

            $this->trace->info(TraceCode::COMMISSION_INVOICES_TO_BE_MIGRATED,[
                'invoice_ids' => $invoiceIds,
                'count'       => $count
            ]);

            $fileStoreIds = $this->repo->file_store->getFileStoreIdWithEntiyId($invoiceIds);

            InvoiceBucketUpdater::dispatch($this->mode, $fileStoreIds, $bucketConfig);
        }

        return ['count' => $count];
    }
}
