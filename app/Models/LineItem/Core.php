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

        $item = $this->createItemIfNotExists($lineItemDetails, $itemDetails, $merchant, $invoice);

        $lineItem = (new Entity)->build($lineItemDetails);

        $this->setLineItemAssociations($lineItem, $merchant, $invoice, $item);

        $this->repo->saveOrFail($lineItem);

        return $lineItem;
    }

    public function update(
        Entity $lineItem,
        array $input,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        list($lineItemDetails, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        if ((isset($lineItemDetails[Entity::ITEM_ID])) or
            (empty($itemDetails) === false))
        {
            $item = $this->createItemIfNotExists(
                $lineItemDetails,
                $itemDetails,
                $merchant,
                $invoice
            );

            $lineItem->item()->associate($item);
        }

        $lineItem->edit($lineItemDetails);

        $this->repo->saveOrFail($lineItem);

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
    protected function createItemIfNotExists(
        array $lineItemDetails,
        array $itemDetails,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        $item = $this->getItemIfIdExistsInInput($lineItemDetails, $merchant);

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
     * @param array           $lineItemDetails
     * @param Merchant\Entity $merchant
     *
     * @return Item\Entity
     */
    protected function getItemIfIdExistsInInput(array $lineItemDetails, Merchant\Entity $merchant)
    {
        $item = null;

        if (isset($lineItemDetails[Entity::ITEM_ID]) === true)
        {
            $item = $this->repo->item->findByPublicIdAndMerchant(
                $lineItemDetails[Entity::ITEM_ID],
                $merchant
            );
        }

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

    protected function setLineItemAssociations(
        Entity $lineItem,
        Merchant\Entity $merchant,
        Base\PublicEntity $entity,
        Item\Entity $item)
    {
        $lineItem->merchant()->associate($merchant);

        $lineItem->entity()->associate($entity);

        $lineItem->item()->associate($item);
    }
}
