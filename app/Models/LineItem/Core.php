<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Merchant;
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
            [
                'input'     => $input,
                'entity_id' => $morphEntity->getId(),
            ]
        );

        $this->modifyInputToHandleRenamedAttributes($input);

        $lineItem = (new Entity)->generateId();

        $this->setItemAssociationAndModifyInput($lineItem, $input, $merchant);

        $this->removeTaxFieldsForInternational($morphEntity, $input, $merchant);

        //
        // For Backward compatibility: If without ITEM_ID (template),
        // no CURRENCY is sent, we use invoice's currency.
        //
        if ((isset($input[Entity::ITEM_ID]) === false) and
            (isset($input[Entity::CURRENCY]) === false))
        {
            $input[Entity::CURRENCY] = $morphEntity->getCurrency();
        }

        //
        // Following associations should happen before build() as these
        // are getting used in validations.
        //
        $lineItem->merchant()->associate($merchant);
        $lineItem->entity()->associate($morphEntity);

        $lineItem->build($input);

        // TODO: Check why following only works when called after build()?
        $this->setRefAssociationIfApplicable($input, $lineItem);

        $existingLineItemsCount = $morphEntity->lineItems()->count();
        $morphEntity->getValidator()->validateLineItemsCount(++$existingLineItemsCount);

        (new Tax\Core)->createLineItemTaxes($lineItem, $input, $merchant);

        $this->calculateAndSetAmountsOfLineItem($lineItem);

        $this->repo->saveOrFail($lineItem);

        return $lineItem;
    }

    public function createMany(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_CREATE_BULK_REQUEST,
            [
                'input' => $input,
                'entity_id' => $morphEntity->getId()
            ]);

        (new Validator)->validateInput(
                            'create_many',
                            [Entity::LINE_ITEMS => $input]);

        $this->repo->transaction(
            function() use ($merchant, $morphEntity, $input)
            {
                foreach ($input as $lineItemInput)
                {
                    $this->create($lineItemInput, $merchant, $morphEntity);
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
                'id'        => $lineItem->getId(),
                'entity_id' => $morphEntity->getId(),
                'input'     => $input,
            ]);

        $this->modifyInputToHandleRenamedAttributes($input);

        $this->setItemAssociationAndModifyInput($lineItem, $input, $merchant);

        $this->removeTaxFieldsForInternational($morphEntity, $input, $merchant);

        $lineItem->edit($input);

        (new Tax\Core)->cleanUpAndCreateLineItemTaxes(
                            $lineItem,
                            $input,
                            $merchant);

        $this->calculateAndSetAmountsOfLineItem($lineItem);

        $this->repo->saveOrFail($lineItem);

        return $lineItem;
    }

    public function delete(Entity $lineItem, Base\PublicEntity $morphEntity)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_DELETE_REQUEST,
            [
                'id'        => $lineItem->getId(),
                'entity_id' => $morphEntity->getId()
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
     * @param array             $lineItemsDetails
     * @param Merchant\Entity   $merchant
     * @param Base\PublicEntity $morphEntity
     */
    public function updateLineItemsAsPut(
        array $lineItemsDetails,
        Merchant\Entity $merchant,
        Base\PublicEntity $morphEntity)
    {
        $this->trace->info(
            TraceCode::LINE_ITEMS_UPDATE_PUT_REQUEST,
            $lineItemsDetails);

        //
        // This must be done before creating the line items
        // since we delete all the line items which are present
        // in the DB but not sent in the input.
        // If we delete after creating, we will end up deleting
        // the newly created line items also.
        //
        $this->deleteLineItemsViaUpdate($morphEntity, $lineItemsDetails);

        $this->createOrUpdateLineItemsViaUpdate(
            $lineItemsDetails, $morphEntity, $merchant);
    }

    // -------------------- Protected methods --------------------

    protected function createOrUpdateLineItemsViaUpdate(
        array $lineItemsDetails,
        Base\PublicEntity $morphEntity,
        Merchant\Entity $merchant)
    {
        foreach ($lineItemsDetails as $lineItemDetails)
        {
            //
            // If id exists in input, find and update the line item.
            // Else, create new line item with given input
            //

            if (array_key_exists(Entity::ID, $lineItemDetails))
            {
                $lineItemId = $lineItemDetails[Entity::ID];
                unset($lineItemDetails[Entity::ID]);

                $lineItem = $this->repo->line_item
                    ->findByPublicIdAndMorphEntity($lineItemId, $morphEntity);

                $this->update($lineItem, $lineItemDetails, $merchant, $morphEntity);
            }
            else
            {
                $this->create($lineItemDetails, $merchant, $morphEntity);
            }
        }
    }

    /**
     * All the line items are deleted which are found in the DB but not in the input.
     *
     * @param Base\PublicEntity $morphEntity
     * @param array             $lineItemsDetails
     */
    protected function deleteLineItemsViaUpdate(
        Base\PublicEntity $morphEntity,
        array $lineItemsDetails)
    {
        $existingLineItems = $morphEntity->lineItems()->get();

        $inputLineItemIds = collect($lineItemsDetails)->pluck('id')->all();

        $existingLineItems->map(
            function($existingLineItem, $i) use ($inputLineItemIds, $morphEntity)
            {
                if (in_array(
                    $existingLineItem->getPublicId(),
                    $inputLineItemIds,
                    true) === false)
                {
                    $this->delete($existingLineItem, $morphEntity);
                }
            });
    }

    protected function setRefAssociationIfApplicable(
        array $input,
        Entity $lineItem)
    {
        //
        // We are passing the ref object in line_item input
        // rather than passing the entity to the function because
        // one invoice can have multiple line_items and addons
        // are associated with the line_item and not an invoice.
        // Hence, while creating an invoice, associating the addons
        // with line_items becomes difficult otherwise.
        //

        if (empty($input[Entity::REF]) === true)
        {
            return;
        }

        $lineItem->ref()->associate($input[Entity::REF]);
    }

    /**
     * If item_id is sent in the input,
     * - associate that item with the line_item
     * - fill up the missing attributes in line_item using
     *   the item attributes
     *
     * @param Entity            $lineItem
     * @param array             $input
     * @param Merchant\Entity   $merchant
     *
     * @return null
     */
    protected function setItemAssociationAndModifyInput(Entity $lineItem, array & $input, Merchant\Entity $merchant)
    {
        if (isset($input[Entity::ITEM_ID]) === false)
        {
            return;
        }

        $item = $this->repo->item->findActiveByPublicIdAndMerchant($input[Entity::ITEM_ID], $merchant);

        $lineItem->item()->associate($item);

        // Use item's values where line item detail is not present, and modify input for line item's build
        $itemFields = array_intersect_key($item->toArrayPublic(), array_flip(Entity::$itemFields));

        //
        // Line_item input allows one of: tax_id, tax_ids, tax_group_id & item supports only tax_id and tax_group_id.
        // Hence, while copying over item's values, we ensure not to add both (tax_id and tax_ids) to line_item input,
        // instead if item.tax_id exists, we push the value into line_item.tax_ids[]
        //
        if ((isset($input[Entity::TAX_IDS]) === true) and (isset($itemFields[Item\Entity::TAX_ID]) === true))
        {
            array_push($input[Entity::TAX_IDS], $itemFields[Item\Entity::TAX_ID]);

            unset($itemFields[Item\Entity::TAX_ID]);
        }

        $input = array_merge($itemFields, $input);
    }

    protected function calculateAndSetAmountsOfLineItem(Entity $lineItem)
    {
        list ($grossAmount, $taxAmount, $netAmount) = $this->calculateAmountsOfLineItem($lineItem);

        $lineItem->setGrossAmount($grossAmount);
        $lineItem->setTaxAmount((int) round($taxAmount));
        $lineItem->setNetAmount((int) round($netAmount));
    }

    /**
     * Calculates gross, tax and net amount for given line items basis all the taxes associated with it. Returns float
     * (precise) value and it's up to caller to round it and store in table's amount column(which is int throughout) or
     * do further operation etc on precise float.
     * @param  Entity $lineItem
     * @return array
     */
    public function calculateAmountsOfLineItem(Entity $lineItem): array
    {
        // Gross amount
        $grossAmount = $lineItem->getAmount() * $lineItem->getQuantity();

        // Tax amount
        $lineItemTaxes = $lineItem->taxes;
        $taxableAmount = Tax\Calculator::getTaxableAmountOfLineItem($lineItem, $lineItemTaxes);
        $taxAmount     = $lineItemTaxes->reduce(
                            function ($carry, $lineItemTax) use ($lineItem, $taxableAmount)
                            {
                                return $carry + Tax\Calculator::getTaxAmount($lineItem, $taxableAmount, $lineItemTax);
                            });

        // Net amount
        $netAmount = $grossAmount + ($lineItem->isTaxInclusive() === false ? $taxAmount : 0);

        return [$grossAmount, $taxAmount, $netAmount];
    }

    /**
     * Modifies input param to handle renamed attributes in response.
     *
     * @param array $input
     */
    protected function modifyInputToHandleRenamedAttributes(array & $input)
    {
        if (array_key_exists(Entity::UNIT_AMOUNT, $input) === true)
        {
            $input[Entity::AMOUNT] = $input[Entity::UNIT_AMOUNT];

            unset($input[Entity::UNIT_AMOUNT]);
        }
    }

    /**
     * Silently remove tax fields for international curreny line items when added directly with out item id.
     * Don't want to keep a hard check on the items tax attributes because in future we might allow the same.
     *
     * @param Base\PublicEntity $morphEntity
     * @param array             $input
     */
    protected function removeTaxFieldsForInternational(Base\PublicEntity $morphEntity, array & $input, $merchant)
    {
        if ((method_exists($morphEntity, 'isGSTTaxationApplicable') === true) and ($morphEntity->isGSTTaxationApplicable($merchant) === false))
        {
            $taxAttributes = Validator::TAX_ATTRIBUTES;

            $input = array_diff_key($input, array_flip($taxAttributes));
        }
    }
}
