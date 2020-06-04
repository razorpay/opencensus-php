<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Services\DiagClient;
use RZP\Services\SalesForceClient;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $core;

    /** @var $diag DiagClient */
    protected $diag;

    /** @var $salesforce SalesForceClient */
    protected $salesforce;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->merchant_attribute;

        $this->diag = $this->app['diag'];

        $this->salesforce = $this->app['salesforce'];
    }

    public function updateMerchantOnboardingCategoryAttributes(Base\PublicCollection $merchantAttributes, $newAttributeValue)
    {
        if ($merchantAttributes->isEmpty())
        {
            $this->trace->info(TraceCode::MERCHANT_ONBOARDING_CATEGORY_CRON_NOTHING_TO_UPDATE, []);

            return;
        }

        $merchantAttributeIds = [];

        /** @var $merchantAttribute Entity */
        foreach ($merchantAttributes as $merchantAttribute)
        {
            array_push($merchantAttributeIds, $merchantAttribute->getId());
        }

        $this->core->bulkUpdateAttributeValuesByIds($merchantAttributeIds, $newAttributeValue);

        // Tracking event of change in attribute
        $merchantEntities = [];

        /** @var $merchantAttribute Entity */
        foreach ($merchantAttributes as $merchantAttribute)
        {
            array_push($merchantEntities, $merchantAttribute->merchant);

            $eventCode = EventCode::MERCHANT_ONBOARDING_CATEGORY_UPDATE;

            $eventProperties = [
                'product' => $merchantAttribute->getProduct(),
                'value'   => $newAttributeValue
            ];

            $this->diag->trackOnboardingEvent(
                $eventCode,
                $merchantAttribute->merchant,
                null,
                $eventProperties);
        }

        $this->salesforce->updateChangeInBankingMerchantOnboardingCategory($merchantEntities, $newAttributeValue);

        $this->trace->info(TraceCode::MERCHANT_ONBOARDING_CATEGORY_CRON_UPDATE_SUCCESSFUL,
            [
                'value'         => $newAttributeValue,
                'merchant_ids'  => $merchantAttributeIds
            ]);
    }

    public function updateSelfServeBankingMerchantsToNormal(array $input)
    {
        $days = $input['days'];

        $product = Product::BANKING;

        $group = Entity::ONBOARDING;

        $type = Entity::MERCHANT_ONBOARDING_CATEGORY;

        $value = Entity::SELF_SERVE;

        // get all the merchantattributeIds that belong to merchants who have been tagged
        // as self-serve $days back, and who have not onboarded yet
        $merchantAttributesToUpdate = $this->core->getAttributesSetBeforeDaysForMerchantsNotOnboarded(
            $product,
            $group,
            $type,
            $value,
            $days
        );

        // Move them to the NORMAL merchant_onboarding_category
        $this->updateMerchantOnboardingCategoryAttributes($merchantAttributesToUpdate, Entity::NORMAL);

        return [];
    }
}
