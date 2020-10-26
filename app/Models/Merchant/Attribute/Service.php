<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Services\DiagClient;
use RZP\Services\SalesForceClient;
use RZP\Models\Merchant\Attribute\Validator;

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

    /**
     * Group & Types - follows flat structure
     * e.g. {"type":"business_category","value": "School"},
     *   {"type":"monthly_payout_count","value": "1000"}
     * Validates Group & Types - Refer to entity to find allowed values map
     * Create or Update Group & Types at Merchant Product Level.
     * At Product, Group & Type level matching happens to identify update entries.
     * @param string $group
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function upsert(string $group, array $input)
    {
        //Validate input for group & type
        $attributeInputValidator = new Validator();
        foreach ($input as $item){
            $attributeInputValidator->validateInput('upsert_input_validation', $item);

            $item[Entity::GROUP] = $group;
            $attributeInputValidator->validateGroupAndType($item);
        }

        $merchant = $this->merchant;
        $product = $this->auth->getRequestOriginProduct();
        $types = array_column($input, 'type');

        $merchantAttributes =  $this->core->fetchKeyValues($merchant, $product, $group, $types);

        //find existing keys
        $merchantAttributesByKeys = $merchantAttributes->getDictionaryByAttribute(Entity::TYPE);
        $existingKeys = array_keys($merchantAttributesByKeys);

        // Do we need transaction? We don't need it. All entries can be saved independently
        foreach ($input as $item){
            $item[Entity::PRODUCT] = $product;
            $item[Entity::GROUP] = $group;
            
            if (in_array($item['type'], $existingKeys)){
                $this->core->update($merchantAttributesByKeys[$item['type']] , $item);
            } else {
                $this->core->create($item, $merchant);
            }
        }

        return $this->core->fetchKeyValues($merchant, $product, $group, $types);
    }

    /**
     * Get merchant preferences by group and type
     * @param string $group
     * @param string|null $type
     * @return mixed
     */
    public function getPreferencesByGroupAndType(string $group, string $type = null)
    {
        $merchant = $this->merchant;
        $product = $this->auth->getRequestOriginProduct();

        if ($type != null) {
            return $this->core->fetchKeyValues($merchant, $product, $group, [$type]);
        } else {
            return $this->core->fetchKeyValues($merchant, $product, $group);
        }
    }
}
