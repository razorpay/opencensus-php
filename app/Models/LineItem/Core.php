<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Models\Invoice;
use RZP\Exception;
use RZP\Trace\TraceCode;
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
        $this->trace->info(
            TraceCode::LINE_ITEM_CREATE_REQUEST,
            [
                'input'         => $input,
                'invoice_id'    => $invoice->getId(),
                'input'         => $input,
            ]);

        list($input, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        $item = $this->createItemIfNotExists($input, $itemDetails, $merchant, $invoice);

        $lineItem = (new Entity)->build($input);

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
        list($input, $itemDetails) = $this->separateItemInputFromLineItemInput($input);

        if (isset($input[Entity::ITEM_ID]) or $itemDetails)
        {
            $item = $this->createItemIfNotExists(
                $input,
                $itemDetails,
                $merchant,
                $invoice
            );

            $lineItem->item()->associate($item);
        }

        $lineItem->edit($input);

        $this->repo->saveOrFail($lineItem);

        return $lineItem;
    }

    public function delete(Entity $lineItem)
    {
        $this->repo->line_item->deleteOrFail($lineItem);

        return [];
    }

    // -------------------- Protected methods --------------------

    protected function createItemIfNotExists(
        array $input,
        array $itemDetails,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        $item = $this->getItemIfIdExistsInInput($input, $merchant);

        if ($item and $item->isNotActive())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ITEM_INACTIVE,
                null,
                [
                    'item_id' => $item->getPublicId(),
                ]
            );
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
            $invoice->getCurrency()
        );

        return $item;
    }

    protected function getItemIfIdExistsInInput(array $input, Merchant\Entity $merchant)
    {
        $item = null;

        if (isset($input[Entity::ITEM_ID]) === true)
        {
            $item = $this->repo->item->findByPublicIdAndMerchant(
                $input[Entity::ITEM_ID],
                $merchant
            );
        }

        return $item;
    }

    /**
     * Request payload contains flattened linesItemDetails,
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

        foreach ($lineItemDetails as $key => $value)
        {
            if (in_array($key, Item\Entity::$allFields, true))
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
        $lineItem->entity()->associate($entity);

        $lineItem->item()->associate($item);

        $lineItem->merchant()->associate($merchant);
    }
}
