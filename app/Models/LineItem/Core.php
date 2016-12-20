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
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Invoice\Entity  $invoice
     *
     * @return Entity
     */
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        list($lineItemDetails, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        $lineItem = (new Entity)->build($lineItemDetails);

        $lineItem->merchant()->associate($merchant);
        $lineItem->entity()->associate($invoice);

        $this->repo->transaction(
            function() use ($merchant, $invoice, $lineItem, $lineItemDetails, $itemDetails)
            {
                $item = $this->createItemOrGetExisting($lineItemDetails, $itemDetails, $merchant, $invoice);

                $lineItem->item()->associate($item);

                $this->repo->saveOrFail($lineItem);
            }
        );

        return $lineItem;
    }

    public function update(
        Entity $lineItem,
        array $input,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        list($lineItemDetails, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        $lineItem->edit($lineItemDetails);

        $this->repo->transaction(
            function() use ($merchant, $invoice, $lineItem, $lineItemDetails, $itemDetails)
            {
                //
                // Updates item association if item_id or item details are sent
                // as part of update api call.
                //

                if ((isset($lineItemDetails[Entity::ITEM_ID])) or
                    (empty($itemDetails) === false))
                {
                    $item = $this->createItemOrGetExisting($lineItemDetails, $itemDetails, $merchant, $invoice);

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
     * @param Invoice\Entity  $invoice
     *
     * @return Item\Entity
     * @throws Exception\BadRequestException
     */
    protected function createItemOrGetExisting(
        array $lineItemDetails,
        array $itemDetails,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        $item = null;

        //
        // If ITEM_ID exists in input, use the existing item for association.
        // But, chekds if item is active or not.
        //

        if (isset($lineItemDetails[Entity::ITEM_ID]) === true)
        {
            $item = $this->repo->item->findByPublicIdAndMerchant(
                $lineItemDetails[Entity::ITEM_ID],
                $merchant
            );
        }

        if (($item !== null) and
            ($item->isNotActive()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ITEM_INACTIVE,
                null,
                [
                    'item_id' => $item->getId(),
                    'invoice_id' => $invoice->getId(),
                ]);
        }

        //
        // Else creates item with given input
        //

        if (empty($item))
        {
            // Use invoice currency if item's currency not in input
            if (isset($itemDetails[Item\Entity::CURRENCY]) === false)
            {
                $itemDetails[Item\Entity::CURRENCY] = $invoice->getCurrency();
            }

            $item = (new Item\Core)->create($itemDetails, $merchant);
        }

        $item->getValidator()->validateCurrency(
            $item->getCurrency(),
            $invoice->getCurrency());

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
