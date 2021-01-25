<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Document;

class InstantActivation extends Base
{
    protected $merchantDocumentCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantDocumentCore = new Document\Core();
    }

    protected function processEntry(array & $entry)
    {
        if (empty($entry[Merchant\Entity::MERCHANT_ID]) === false)
        {
            $this->repo->transactionOnLiveAndTest(function() use (& $entry) {

                $merchant = $this->repo->merchant->findOrFail(trim($entry[Merchant\Entity::MERCHANT_ID]));

                $merchantDetail = $merchant->merchantDetail;

                if ($merchantDetail->getBusinessType() === Merchant\Detail\BusinessType::PROPRIETORSHIP)
                {

                    list($docToMigrate, $isDocAlreadyMigrated) =
                        $this->getDocToMigrateAndDocumentMigrationStatus($merchant);

                    if (($isDocAlreadyMigrated === false) and
                        ($docToMigrate !== null))
                    {
                        $this->migrateDocument($docToMigrate, $merchant);
                    }
                }

            });

            $entry[Header::STATUS] = Status::SUCCESS;
        }
    }

    /**
     * @param $docToMigrate
     * @param $merchant
     *
     * @throws \RZP\Exception\BadRequestException
     */
    function migrateDocument(Document\Entity $docToMigrate, Merchant\Entity $merchant): void
    {
        $documentParams = [
            Document\Type::PERSONAL_PAN => [
                Document\Constants::FILE_ID => $docToMigrate->getFileStoreId(),
                Document\Constants::SOURCE  => $docToMigrate->getFileStoreSource(),
            ]
        ];

        $this->merchantDocumentCore->storeInMerchantDocument($merchant, $merchant, $documentParams, null);
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    function getDocToMigrateAndDocumentMigrationStatus(Merchant\Entity $merchant): array
    {
        $merchantDocumemnts = $merchant->merchantDocuments;

        $businessPanDocument = null;

        $isDocAlreadyMigrated = false;

        foreach ($merchantDocumemnts as $merchantDocumemnt)
        {
            if ($merchantDocumemnt->getDocumentType() === Document\Type::BUSINESS_PAN_URL)
            {
                $businessPanDocument = $merchantDocumemnt;

                continue;
            }

            if ($merchantDocumemnt->getDocumentType() === Document\Type::PERSONAL_PAN)
            {
                $isDocAlreadyMigrated = true;
            }
        }

        return [$businessPanDocument, $isDocAlreadyMigrated];
    }
}
