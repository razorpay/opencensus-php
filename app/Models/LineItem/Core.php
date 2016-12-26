<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Models\Invoice;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant, Base\Entity $morphEntity)
    {
        list($lineItemDetails, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        $lineItem = (new Entity)->build($lineItemDetails);

        $lineItem->merchant()->associate($merchant);
        $lineItem->entity()->associate($morphEntity);

        $this->repo->transaction(
            function() use ($merchant, $morphEntity, $lineItem, $lineItemDetails, $itemDetails)
            {
                //
                // Create or get item(if id exists in input) and associate with line item.
                //

                $item = $this->createItemOrGetExisting($lineItemDetails, $itemDetails, $merchant, $morphEntity);

                $lineItem->item()->associate($item);

                $this->repo->saveOrFail($lineItem);
            });

        return $lineItem;
    }

    public function createMany(array $input, Merchant\Entity $merchant, Base\Entity $morphEntity)
    {
        (new Validator)->validateInput('create_many', $input);

        $this->repo->transaction(
            function() use ($merchant, $morphEntity, $input)
            {
                foreach ($input[Entity::LINE_ITEMS] as $singleLineItemInput)
                {
                    $this->create($singleLineItemInput, $merchant, $morphEntity);
                }
            });
    }

    public function update(
        Entity $lineItem,
        array $input,
        Merchant\Entity $merchant,
        Base\Entity $morphEntity)
    {
        list($lineItemDetails, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        $lineItem->edit($lineItemDetails);

        $this->repo->transaction(
            function() use ($merchant, $morphEntity, $lineItem, $lineItemDetails, $itemDetails)
            {
                //
                // Updates item association if item_id or item details are sent
                // as part of update input.
                //

                if ((isset($lineItemDetails[Entity::ITEM_ID])) or
                    (empty($itemDetails) === false))
                {
                    $item = $this->createItemOrGetExisting($lineItemDetails, $itemDetails, $merchant, $morphEntity);

                    $lineItem->item()->associate($item);
                }

                $this->repo->saveOrFail($lineItem);
            }
        );

        return $lineItem;
    }

    public function delete(Entity $lineItem)
    {
        return $this->repo->line_item->deleteOrFail($lineItem);
    }

    public function deleteMany(Base\PublicCollection $lineItems)
    {
        $this->repo->transaction(
            function() use ($lineItems)
            {
                foreach ($lineItems as $lineItem)
                {
                    $this->repo->line_item->deleteOrFail($lineItem);
                }
            });
    }

    public function getInvoiceAmountForLineItems(Base\PublicCollection $lineItems)
    {
        $totalAmount = 0;

        foreach ($lineItems as $lineItem)
        {
            $totalAmount += ($lineItem->getQuantity() * $lineItem->item->getAmount());
        }

        return $totalAmount;
    }

    // -------------------- Protected methods --------------------

    /**
     * @param array           $lineItemDetails
     * @param array           $itemDetails
     * @param Merchant\Entity $merchant
     * @param Base\Entity     $morphEntity
     *
     * @return Item\Entity
     * @throws Exception\BadRequestException
     */
    protected function createItemOrGetExisting(
        array $lineItemDetails,
        array $itemDetails,
        Merchant\Entity $merchant,
        Base\Entity $morphEntity)
    {
        $item = null;

        //
        // If ITEM_ID exists in input, use the existing active item for association.
        //

        if (isset($lineItemDetails[Entity::ITEM_ID]) === true)
        {
            $item = $this->repo->item->findActiveByPublicIdAndMerchantOrFail(
                $lineItemDetails[Entity::ITEM_ID],
                $merchant
            );
        }

        //
        // Else creates item with given input
        //

        if (empty($item))
        {
            //
            // Use morphEntity's currency if item's currency not in input
            //

            if (isset($itemDetails[Item\Entity::CURRENCY]) === false)
            {
                $itemDetails[Item\Entity::CURRENCY] = $morphEntity->getCurrency();
            }

            $item = (new Item\Core)->create($itemDetails, $merchant);
        }

        $item->getValidator()->validateCurrency(
            $item->getCurrency(),
            $morphEntity->getCurrency());

        return $item;
    }

    /**
     * Request payload contains flattened lineItemDetails,
     * i.e. It has line item attributes (eg. quantity) and
     * the contained item attributes (eg. name, amount etc.).
     *
     * This function separates those payloads for it to be used further.
     *
     * @param array $lineItemDetails
     *
     * @return array
     */
    protected function separateItemInputFromLineItemInput(array $lineItemDetails)
    {
        $itemDetails = [];

        $itemFields = Item\Entity::$allFields;

        foreach ($lineItemDetails as $key => $value)
        {
            if (in_array($key, $itemFields, true))
            {
                $itemDetails[$key] = $value;

                unset($lineItemDetails[$key]);
            }
        }

        return [$lineItemDetails, $itemDetails];
    }
}
