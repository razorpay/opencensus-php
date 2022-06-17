<?php


namespace RZP\Models\Order\Product;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Feature;

class Core extends Base\Core
{
    public function create(Order\Entity $order, array $productArray)
    {
        (new Validator)->validateCreateProduct($productArray);

        $productType = $productArray[Entity::TYPE];

        // unsetting here because we are storing type in `product_type` column
        // while returning to merchant (toArrayPublic()) we merge product_type and product json and return
        unset($productArray[Entity::TYPE]);

        $input = [
            Entity::ORDER_ID       => $order->getId(),
            Entity::PRODUCT_TYPE   => $productType,
            Entity::PRODUCT        => $productArray,
        ];

        $product = (new Entity)->build($input);

        $this->repo->product->saveOrFail($product);

        return $product;
    }

    public function createMany(Order\Entity $order, $productsArray)
    {
        (new Validator)->validateCreateMany($productsArray);

        if ($order->merchant->isFeatureEnabled(Feature\Constants::CART_API_AMOUNT_CHECK) === true)
        {
            (new Validator)->validateOrderAmountWithProductTotal($order->getAmount(), $productsArray);
        }

        $this->repo->product->transaction(function() use ($order, $productsArray){
            foreach ($productsArray as $productArray)
            {
                $this->create($order, $productArray);
            }
        });
    }
}
