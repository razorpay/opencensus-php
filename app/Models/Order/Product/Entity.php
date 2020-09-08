<?php


namespace RZP\Models\Order\Product;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const ORDER_ID      = 'order_id';

    // this is the type of the product sent by the merchant in order create.
    // this is not related to the product_type field present in order entity
    const PRODUCT_TYPE  = 'product_type';
    const PRODUCT       = 'product';

    const TYPE  = 'type';

    const ID_LENGTH = 14;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ORDER_ID,
        self::PRODUCT_TYPE,
        self::PRODUCT,
    ];

    protected $visible = [
        self::ID,
        self::ORDER_ID,
        self::PRODUCT_TYPE,
        self::PRODUCT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::PRODUCT       => 'array',
    ];

    protected $entity = Constants\Entity::PRODUCT;

    public function toArrayPublic()
    {
        // while storing in db, we are storing json in `product` column and type in `product_type` column
        // we merge into same array and return to merchant
        $array = $this->getProduct();

        $array[Entity::TYPE] = $this->getProductType();

        return $array;
    }

    public function getProductType()
    {
        return $this->getAttribute(self::PRODUCT_TYPE);
    }

    public function getProduct()
    {
        return $this->getAttribute(self::PRODUCT);
    }
}
