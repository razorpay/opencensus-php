<?php

namespace RZP\Models\Merchant\InternationalEnablement;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\InternationalEnablement\Document\Constants as DocConstants;
use RZP\Models\Merchant\InternationalEnablement\Document\Repository as IEDocumentRepository;

class Core extends Base\Core
{
    public function preview(): array
    {
        $detailEntity = (new Detail\Core)->getLatest();

        $enablementProgress = Constants::NOT_STARTED;

        $percentageCompletion = 0;

        $lastUpdatedAt = NULL;

        if (is_null($detailEntity) === false)
        {
            if ($detailEntity->isSubmitted() === true)
            {
                $enablementProgress = Constants::SUBMITTED;
            }
            else
            {
                $enablementProgress = Constants::IN_PROGRESS;
            }

            $percentageCompletion = $this->getPercentageCompletion($detailEntity);

            $lastUpdatedAt = $detailEntity->getUpdatedAt();
        }

        $newFlow = $this->routeThroughNewFlow();

        return [
            Constants::ENABLEMENT_PROGRESS   => $enablementProgress,
            Constants::PERCENTAGE_COMPLETION => $percentageCompletion,
            Constants::NEW_FLOW              => $newFlow,
            Constants::LAST_UPDATED_AT       => $lastUpdatedAt,
        ];
    }

    public function get(): ?Detail\Entity
    {
        return (new Detail\Core)->getLatest();
    }

    public function upsert(array $input, string $action, $version = 'v1'): Detail\Entity
    {
        $documents = [];

        if (array_key_exists(Detail\Entity::DOCUMENTS, $input) === true)
        {
            $documents = $input[Detail\Entity::DOCUMENTS];
        }

        unset($input[Detail\Entity::DOCUMENTS]);

        $newDetailEntity = $this->repo->transaction(function() use ($input, $documents, $action, $version)
        {
            list($oldDetailEntity, $newDetailEntity) =
                (new Detail\Core)->upsert($input, $action);

            if (is_null($documents) === true)
            {
                $documents = [];
            }

            (new Document\Core)->upsertBulk($oldDetailEntity, $newDetailEntity, $documents, $action, $version);

            return $newDetailEntity;
        });

        return $newDetailEntity;
    }

    public function discard()
    {
        $discardedEntity = $this->repo->transaction(function()
        {
            $detailEntity = (new Detail\Core)->getLatest();

            if ((is_null($detailEntity) === true) or ($detailEntity->isSubmitted() === true))
            {
                return null;
            }

            $detailEntity->documents()->delete();

            $detailEntity->delete();

            return $detailEntity;
        });

        return $discardedEntity;
    }

    private function getPercentageCompletion(Detail\Entity $detailEntity)
    {
        $actualAttributesPresent = 0;

        $requiredAttributesForCalculation = $this->convertToExternalFormat($detailEntity);

        $documents = $requiredAttributesForCalculation[Detail\Entity::DOCUMENTS] ?? [];

        unset($requiredAttributesForCalculation[Detail\Entity::DOCUMENTS]);

        $removeAttributesForCalculation = [
            Detail\Entity::CREATED_AT,
            Detail\Entity::UPDATED_AT,
            Detail\Entity::SUBMITTED_AT,
            Detail\Entity::PRODUCTS,
            Detail\Entity::IMPORT_EXPORT_CODE,
            Detail\Entity::SOCIAL_MEDIA_PAGE_LINK,
            Detail\Entity::ALLOWED_CURRENCIES,
            Detail\Entity::MONTHLY_SALES_INTL_CARDS_MIN,
            Detail\Entity::MONTHLY_SALES_INTL_CARDS_MAX,
            Detail\Entity::LOGISTIC_PARTNERS,
            Detail\Entity::CONTACT_US_LINK,
            Detail\Entity::TERMS_AND_CONDITIONS_LINK,
            Detail\Entity::PRIVACY_POLICY_LINK,
            Detail\Entity::REFUND_AND_CANCELLATION_POLICY_LINK,
            Detail\Entity::SHIPPING_POLICY_LINK,
            Detail\Entity::SOCIAL_MEDIA_PAGE_LINK,
            Detail\Entity::CUSTOMER_INFO_COLLECTED,
            Detail\Entity::PARTNER_DETAILS_PLUGINS,
        ];

        $goodsType = $requiredAttributesForCalculation[Detail\Entity::GOODS_TYPE] ?? '';

        if ($goodsType === Detail\Constants::GOODS_TYPE_DIGITAL_SERVICES)
        {
            $removeAttributesForCalculation[] = Detail\Entity::LOGISTIC_PARTNERS;

            $removeAttributesForCalculation[] = Detail\Entity::SHIPPING_POLICY_LINK;
        }

        // unset unwanted values
        foreach ($removeAttributesForCalculation as $attr)
        {
            unset($requiredAttributesForCalculation[$attr]);
        }

        $totalAttributesForCalculation = count($requiredAttributesForCalculation);

        // % calculation for details

        foreach ($requiredAttributesForCalculation as $value)
        {
            if(is_null($value) === false)
            {
                ++$actualAttributesPresent;
            }
        }

        // % calculation for documents

        $acceptsIntlTxn = $requiredAttributesForCalculation[Detail\Entity::ACCEPTS_INTL_TXNS];

        if ($acceptsIntlTxn === true)
        {
            $requiredDocs = Document\Constants::MANDATORY_DOCUMENT_TYPES;

            $suppliedDocs = array_keys($documents);

            $missingDocs = array_diff($requiredDocs, $suppliedDocs);

            $actualAttributesPresent += count($requiredDocs) - count($missingDocs);

            $totalAttributesForCalculation += count($requiredDocs);
        }

        return intval($actualAttributesPresent * 100 / $totalAttributesForCalculation);
    }

    private function routeThroughNewFlow(): bool
    {
        $mode = $this->app['rzp.mode'];

        $merchantId = $this->merchant->getId();

        $variant = $this->app->razorx->getTreatment($merchantId, Constants::IE_RAZORX_FEATURE, $mode);

        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_QUESTIONNAIRE_RAZORX_VARIANT, [
            'merchant_id'     => $merchantId,
            'razorx_variant'  => $variant,
        ]);

        return ($variant === Constants::RAZORX_VARIANT_NEW_FLOW);
    }

    public function deleteCancelledDocs(Detail\Entity $detailEntity, array $input)
    {
        $inputDocs = [];

        $inputCustomDocs = [];

        $merchantId = $this->merchant->getId();

        if (array_key_exists(Detail\Entity::DOCUMENTS, $input) === true)
        {
            $inputDocs = $input[Detail\Entity::DOCUMENTS];

            if ($inputDocs !== null)
            {
                if (array_key_exists(DocConstants::OTHERS, $inputDocs) === true)
                {
                    $inputCustomDocs = $inputDocs[DocConstants::OTHERS];

                    unset($inputDocs[DocConstants::OTHERS]);
                }

                foreach ($inputDocs as $docType => $docDetail)
                {
                    if ($docDetail === null)
                    {
                        $docEntity = (new IEDocumentRepository())->fetchDocumentByMerchantIdAndIEDetailIdAndType($merchantId, $detailEntity->getId(), $docType);

                        if ($docEntity !== null)
                        {
                            (new IEDocumentRepository())->deleteOrFail($docEntity);
                        }
                    }
                }

                foreach ($inputCustomDocs as $customDocType => $customDocDetail)
                {
                    if ($customDocDetail === null)
                    {
                        $docEntity = (new IEDocumentRepository())->fetchOtherDocumentByMerchantIdAndIEDetailIdAndCustomType($merchantId, $detailEntity->getId(), $customDocType);

                        if ($docEntity !== null)
                        {
                            (new IEDocumentRepository())->deleteOrFail($docEntity);
                        }
                    }
                }
            }
        }
    }

    public function convertToExternalFormat(Detail\Entity $detailEntity)
    {
        $publicAttributes = $detailEntity->toArrayPublic();

        $documents = (new Document\Core)->convertDocObjectsToExternalFormat($detailEntity->documents);

        if (empty($documents) === true)
        {
            $documents = null;
        }

        $publicAttributes['documents'] = $documents;

        return $publicAttributes;
    }

    /**
     * This returns enablement form data required for international visibility,
     * for a merchant.
     * @return array
     */
    public function getInternationalEnablementDetail(): array
    {
        $detailEntity = (new Detail\Core)->getLatest();
        $isInternational = $this->merchant->isInternational();

        $internationalFormCompleted = false;
        $internationalFormInitiated = false;

        if (is_null($detailEntity) === false)
        {
            $internationalFormCompleted = $detailEntity->isSubmitted();
            $internationalFormInitiated = !$internationalFormCompleted;
        }
        if ($isInternational === true) {
            $internationalFormCompleted = true;
        }

        return [
            Constants::INTERNATIONAL_CARDS_ENABLED              => $internationalFormCompleted,
            Constants::INTERNATIONAL_ACTIVATION_FORM_INITIATED  => $internationalFormInitiated,
            Constants::INTERNATIONAL_ACTIVATION_FORM_COMPLETED  => $internationalFormCompleted,
        ];
    }
}
