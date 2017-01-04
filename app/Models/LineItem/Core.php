<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_CREATE_REQUEST,
            $input
        );

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

                $item = $this->createItemOrGetExisting(
                    $lineItemDetails,
                    $itemDetails,
                    $merchant,
                    $morphEntity);

                $lineItem->item()->associate($item);

                $this->repo->saveOrFail($lineItem);
            });

        return $lineItem;
    }

    public function createMany(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_CREATE_BULK_REQUEST,
            $input
        );

        (new Validator)->validateInput('create_many', [Entity::LINE_ITEMS => $input]);

        $this->repo->transaction(
            function() use ($merchant, $morphEntity, $input)
            {
                foreach ($input as $singleLineItemInput)
                {
                    $this->create($singleLineItemInput, $merchant, $morphEntity);
                }
            });
    }

    public function update(
        Entity $lineItem,
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_UPDATE_REQUEST,
            [
                'id'    => $lineItem->getId(),
                'input' => $input,
            ]);

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
                    $item = $this->createItemOrGetExisting(
                        $lineItemDetails,
                        $itemDetails,
                        $merchant,
                        $morphEntity);

                    $lineItem->item()->associate($item);
                }

                $this->repo->saveOrFail($lineItem);
            }
        );

        return $lineItem;
    }

    public function delete(Entity $lineItem)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_DELETE_REQUEST,
            [
                'id' => $lineItem->getId(),
            ]);

        return $this->repo->line_item->deleteOrFail($lineItem);
    }

    public function deleteMany(Base\PublicCollection $lineItems)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_DELETE_BULK_REQUEST,
            [
                'ids' => $lineItems->pluck('id')->toArray(),
            ]);

        $this->repo->transaction(
            function() use ($lineItems)
            {
                foreach ($lineItems as $lineItem)
                {
                    $this->repo->line_item->deleteOrFail($lineItem);
                }
            });
    }

    public function getTotalAmountOfLineItems(Base\PublicEntity $morphEntity)
    {
        $totalAmount = 0;

        $lineItems = $morphEntity->lineItems()->get();

        foreach ($lineItems as $lineItem)
        {
            $totalAmount += ($lineItem->getQuantity() * $lineItem->item->getAmount());
        }

        return $totalAmount;
    }

    /**
     * Updates line items collection of given entity.
     *
     * Eg.
     * [
     *     {
     *         "item_id" : "item_123",     // This line item will be created
     *         "quantity": 10
     *     },
     *     {
     *         "id"      : "li_123"        // This will be patched with
     *                                     // Existing line_item
     *         "item_id" : "item_123",
     *         "quantity": 10
     *     }
     *                                     // If there was a line item for this
     *                                     // invoice with id = li_456, all such
     *                                     // will be deleted.
     * ]
     *
     * @param array             $input
     * @param Merchant\Entity   $merchant
     * @param Base\PublicEntity $morphEntity
     *
     * @return Core
     */
    public function updateLineItems(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
    {
        $oldLineItemsCollection = $morphEntity->lineItems()->get();

        $inputLineItemIds = collect($input)->pluck('id')->all();

        foreach ($input as $lineItemDetails)
        {
            //
            // If id exists in input, find and update the line item
            //

            if (array_key_exists(Entity::ID, $lineItemDetails))
            {
                $id = $lineItemDetails[Entity::ID];
                unset($lineItemDetails[Entity::ID]);

                $lineItem = $this->repo->line_item
                                       ->findByPublicIdAndMorphEntity($id, $morphEntity);

                $this->update($lineItem, $lineItemDetails, $merchant, $morphEntity);
            }

            //
            // Else, create new line item with given input
            //

            else
            {
                $this->create($lineItemDetails, $merchant, $morphEntity);
            }
        }

        //
        // Clean old line items which were not sent in the input
        //

        $oldLineItemsCollection->map(
            function($lineItem, $i) use ($inputLineItemIds)
            {
                if (in_array($lineItem->getPublicId(), $inputLineItemIds, true) === false)
                {
                    $this->delete($lineItem);
                }
            });

        return $this;
    }

    // -------------------- Protected methods --------------------

    /**
     * @param array             $lineItemDetails
     * @param array             $itemDetails
     * @param Merchant\Entity   $merchant
     * @param Base\PublicEntity $morphEntity
     *
     * @return Item\Entity
     * @throws Exception\BadRequestException
     */
    protected function createItemOrGetExisting(
        array $lineItemDetails,
        array $itemDetails,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
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
