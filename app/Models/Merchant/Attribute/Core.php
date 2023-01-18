<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Constants\Mode;
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

    public function fetchKeyValuesByMode(Merchant\Entity $merchant, string $product, string $group, array $types = [], string $column = null, string $orderType = 'asc', string $mode = Mode::TEST)
    {
        return $this->repo->merchant_attribute->connection($mode)
            ->getKeyValues($merchant->getId(), $product, $group, $types, $column, $orderType);
    }

    public function fetchKeyValues(Merchant\Entity $merchant, string $product, string $group, array $types = [], string $column = null, string $orderType = 'asc')
    {
        return $this->repo->merchant_attribute
                ->getKeyValues($merchant->getId(), $product, $group, $types, $column, $orderType);
    }

    public function fetchKeyValuesByMerchantId(string $merchantId, string $product, string $group, array $types = [])
    {
        return $this->repo->merchant_attribute
            ->getKeyValues($merchantId, $product, $group, $types);
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

    public function isXVaActivated(Merchant\Entity $merchant) : bool
    {
        $vaActivated = $this->repo->merchant_attribute->getValueForProductGroupType($merchant->getId(), PRODUCT::BANKING, Merchant\Attribute\Group::PRODUCTS_ENABLED, Merchant\Attribute\Type::X)[Entity::VALUE] ?? "false";

        return ($vaActivated === "true");
    }

    public function bulkUpdateAttributeValuesByIds(array $merchantAttributeIds, $newAttributeValue)
    {
        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTE_BULK_UPDATE_REQUEST,
            [
                'value'         => $newAttributeValue,
                'merchant_ids'  => $merchantAttributeIds
            ]);

        $this->repo->merchant_attribute->updateMerchantAttributeValuesById($merchantAttributeIds, $newAttributeValue);

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTE_BULK_UPDATE,
                [
                    'value'         => $newAttributeValue,
                    'merchant_ids'  => $merchantAttributeIds
                ]);
    }

    public function deactivateMerchantAttributeByGroupAndTypes(string $merchantId, string $group, array $type): array
    {
        $merchant_attributes = $this->repo->merchant_attribute->getKeyValuesForAllProduct($merchantId, $group, $type);
        $response            = array();
        foreach ($merchant_attributes as $merchant_attribute)
        {
            $response[] = $this->update($merchant_attribute, ["value"   => null,
                                                              "type"    => $merchant_attribute->type,
                                                              "product" => $merchant_attribute->product,
                                                              "group"   => $merchant_attribute->group]);
        }

        return $response;
    }

    public function updateMerchantAttributeByGroupTypesAndValue(string $merchantId, string $group, array $type, string $value): array
    {
        $merchant_attributes = $this->repo->merchant_attribute->getKeyValuesForAllProduct($merchantId, $group, $type);
        $response = array();
        foreach ($merchant_attributes as $merchant_attribute) {
            $response[] = $this->update($merchant_attribute, ["value" => $value,
                                                              "type"  => $merchant_attribute->type, "product" => $merchant_attribute->product,
                                                              "group" => $merchant_attribute->group]);
        }
        return $response;
    }
}
