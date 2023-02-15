<?php

namespace RZP\Models\Invoice;

use RZP\Constants\Entity as E;
use RZP\Models\Batch;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Plan\Subscription;
use RZP\Trace\TraceCode;

class OPGSPImportInvoiceCore extends Core
{
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Subscription\Entity $subscription = null,
        Batch\Entity $batch = null,
        Base\Entity $externalEntity = null,
        string $batchId = null,
        Order\Entity $order = null): Entity
    {
        $importInvoiceEntity = new Entity;

        $importInvoiceEntity->merchant()->associate($merchant);

        $importInvoiceEntity->entity()->associate($externalEntity);

        $importInvoiceEntity->build($input);

        $this->repo->saveOrFail($importInvoiceEntity);

        $this->trace->info(TraceCode::OPGSP_INVOICE_SAVE_SUCCESS,[
            'data' =>  $importInvoiceEntity,
        ]);

        return $importInvoiceEntity;
    }

    public function findByPaymentIdDocumentType($paymentId, $documentType)
    {
        return $this->repo->invoice->findByPaymentIdDocumentType($paymentId, E::PAYMENT, $documentType);
    }

    public function findByPaymentIds($paymentIds, $merchantId)
    {
        return $this->repo->invoice->findByPaymentIds($paymentIds, E::PAYMENT, $merchantId);
    }

    public function findByMerchantIdDocumentTypeDocumentNumber($merchantId, $documentType, $documentNumber)
    {
        return $this->repo->invoice
            ->findByMerchantIdDocumentTypeDocumentNumber($merchantId, $documentType, $documentNumber);
    }

    public function findByPaymentId($paymentId)
    {
        return $this->repo->invoice->fetchInvoicesByEntity($paymentId, E::PAYMENT);
    }
}
