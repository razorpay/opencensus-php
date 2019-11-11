<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;

class InstantActivation extends Base
{
    public function __construct(Entity $batch)
    {
        parent::__construct($batch);
    }

    protected function processEntry(array & $entry)
    {
        $this->repo->transactionOnLiveAndTest(function() use (& $entry) {

            $merchantDetails = $this->repo->merchant_detail->getByMerchantId(trim($entry[Merchant::MERCHANT_ID]));

            $documents = $this->getDocumentFieldsInMerchantDetail($merchantDetails);

            foreach ($documents as $documentType => $fileStoreId)
            {
                if ((isset($fileStoreId) === true) and ($this->isEntryAlreadyExistInMerchantDocument($fileStoreId) === false))
                {
                    $documentEntries = [
                        DocumentEntity::MERCHANT_ID   => $merchantDetails->getMerchantId(),
                        DocumentEntity::DOCUMENT_TYPE => $documentType,
                        DocumentEntity::FILE_STORE_ID => $fileStoreId,
                        DocumentEntity::ENTITY_TYPE   => 'merchant',
                    ];

                    $document = (new DocumentEntity())->generateId()->fill($documentEntries);

                    $this->repo->merchant_document->saveOrFail($document);
                }
            }
        });

        $entry[Header::STATUS] = Status::SUCCESS;
    }

    public function isEntryAlreadyExistInMerchantDocument($fileStoreId)
    {
        $document = $this->repo->merchant_document->findDocumentByFileStoreId($fileStoreId);

        return isset($document);
    }

    /**
     * @param DetailEntity $merchantDetails
     *
     * @return array
     */
    private function getDocumentFieldsInMerchantDetail(DetailEntity $merchantDetails): array
    {
        $entityInputKeys = [
            DetailEntity::BUSINESS_PROOF_URL,
            DetailEntity::BUSINESS_OPERATION_PROOF_URL,
            DetailEntity::BUSINESS_PAN_URL,
            DetailEntity::ADDRESS_PROOF_URL,
            DetailEntity::PROMOTER_PROOF_URL,
            DetailEntity::PROMOTER_PAN_URL,
            DetailEntity::PROMOTER_ADDRESS_URL,
            DetailEntity::FORM_12A_URL,
            DetailEntity::FORM_80G_URL,
        ];

        $entityInput = array_only($merchantDetails->getAttributes(), $entityInputKeys);

        // remove null values from input array
        return array_filter($entityInput, function($var) {
            return (is_null($var) === false);
        });
    }
}
