<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Exception\RuntimeException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Services\DiagClient;
use RZP\Exception\LogicException;
use RZP\Services\SalesForceClient;


class Core extends Base\Core
{
    /** @var $diag DiagClient */
    protected $diag;

    /** @var $salesforce SalesForceClient */
    protected $salesforce;

    public function __construct()
    {
        parent::__construct();

        $this->diag = $this->app['diag'];

        $this->salesforce = $this->app['salesforce'];
    }

    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $newAttributeEntity = new Entity;

        $newAttribute = $newAttributeEntity->build($input);

        $newAttribute->merchant()->associate($merchant);

        $this->repo->saveOrFail($newAttribute);

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTE_CREATE, $newAttribute->toArrayPublic());

        return $newAttribute;
    }

    public function fetch(Merchant\Entity $merchant, string $product, string $group, string $type)
    {
        return $this->repo->merchant_attribute
                          ->getValue($merchant, $product, $group, $type);
    }

    public function update(Entity $merchantAttribute, array $input): Entity
    {
        $merchantAttribute->edit($input);

        $this->repo->saveOrFail($merchantAttribute);

        return $merchantAttribute;
    }

    public function delete(Entity $merchantAttribute)
    {
        $this->repo->deleteOrFail($merchantAttribute);

        return $merchantAttribute->toArrayDeleted();
    }

    public function bulkUpdateAttributeValuesByIds(array $merchantAttributeIds, $newAttributeValue)
    {
        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTE_BULK_UPDATE_REQUEST,
            [
                'value'  => $newAttributeValue,
                'merchant_ids' => $merchantAttributeIds
            ]);

        $this->repo->merchant_attribute->updateMerchantAttributeValuesById($merchantAttributeIds, $newAttributeValue);

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTE_BULK_UPDATE,
                [
                    'value'  => $newAttributeValue,
                    'merchant_ids' => $merchantAttributeIds
                ]);
    }

    // -------------------- Merchant Onboarding Category Attribute starts ------------------------//

    public function getMerchantOnboardingCategory(Merchant\Entity $merchant, string $product)
    {
        if ($product == Product::BANKING)
        {
            $variant = $this->app->razorx->getTreatment($merchant->getId(),
                                                        Merchant\RazorxTreatment::X_MERCHANT_SELF_SERVE_ONBOARDING,
                                                        $this->mode);
            if ($variant == 'on')
            {
                return Entity::SELF_SERVE;
            }
        }

        // default
        return Entity::NORMAL;
    }

    public function createMerchantOnboardingCategoryAttribute(Merchant\Entity $merchant, $product)
    {
        $group = Entity::ONBOARDING;

        $type = Entity::MERCHANT_ONBOARDING_CATEGORY;

        $merchantOnboardingCategory = $this->getMerchantOnboardingCategory($merchant, $product);

        $input = [
            Entity::PRODUCT     => $product,
            Entity::GROUP       => $group,
            Entity::TYPE        => $type,
            Entity::VALUE       => $merchantOnboardingCategory
        ];

        $merchantAttribute = $this->create($input, $merchant);

        $this->diag->trackOnboardingEvent(
            EventCode::MERCHANT_ONBOARDING_CATEGORY_SET,
            $merchant,
            null,
            [
                'product' => $product,
                'value'   => $merchantOnboardingCategory
            ]
        );

        return $merchantAttribute;
    }

    public function fetchMerchantOnboardingCategoryAttribute(Merchant\Entity $merchant, $product)
    {
        $group = Entity::ONBOARDING;

        $type = Entity::MERCHANT_ONBOARDING_CATEGORY;

        $mocAttribute = $this->fetch($merchant, $product, $group, $type);

        if ($mocAttribute === null)
        {
            // attribute could be null in the event that insert into DB fails.
            // We fail gracefully during creation of attribute by catching
            // exception and logging trace. This is done so that the main merchant signup
            // flow is not disrupted.
            throw new ServerErrorException("Unable to fetch merchant Onboarding Category",
            ErrorCode::SERVER_ERROR_MERCHANT_ONBOARDING_CATEGORY_FETCH_FAILED,
            [
                'merchant_id' => $merchant->getId(),
                'product'     => $product
            ]);
        }
        return $mocAttribute;
    }



    public function getAttributeIdsSetBeforeDaysForMerchantsNotOnboarded(string $product, string $group, string $type, $value, int $days)
    {
        // convert days to start, end epoch timestamps
        $timeNow = time();

        $timeDaysAgo = strtotime(strval(-1*$days).' days', $timeNow);

        $timeBeginOfDaysAgo = strtotime("today", $timeDaysAgo);

        $timeEndOfDaysAgo = strtotime("tomorrow", $timeBeginOfDaysAgo) - 1;

        return $this->repo->merchant_attribute->getAttributeIdsSetBetweenForMerchantsNotOnboarded(
            $product,
            $group,
            $type,
            $value,
            $timeBeginOfDaysAgo,
            $timeEndOfDaysAgo);
    }

    // -------------------- Merchant Onboarding Mechanism Attribute ends ------------------------//
}
