<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Invoice;
use RZP\Models\Item;
use RZP\Exception;
use RZP\Trace\TraceCode;

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

        // If ITEM_ID set use that otherwise create new item using details provided
        if (isset($input[Entity::ITEM_ID]) === true)
        {
            $item = $this->repo->item->findByPublicIdAndMerchant(
                $input[Entity::ITEM_ID],
                $merchant
            );

            $this->throwIfCurrencyNotSame($item->getCurrency(), $invoice);
        }
        else
        {
            if (isset($input[Item\Entity::CURRENCY]))
            {
                $this->throwIfCurrencyNotSame($input[Item\Entity::CURRENCY], $invoice);
            }
            else
            {
                $input[Item\Entity::CURRENCY] = $invoice->getCurrency();
            }

            list($input, $itemDetails) = $this->separateInput($input);

            $item = (new Item\Core)->create($itemDetails, $merchant);
        }

        $lineItem = (new Entity)->build($input);

        // Associates invoice & item to this line item
        $lineItem->entity()->associate($invoice);
        $lineItem->item()->associate($item);

        $lineItem->merchant()->associate($merchant);

        $this->repo->saveOrFail($lineItem);

        return $lineItem;
    }



    /**
     * Request payload contains flattened linesItemDetails, i.e. It has line item attributes
     *     (eg. quantity) and the contained item attributes (eg. name, amount etc.).
     *     This function separates those payloads for it to be used further.
     *
     * @param array $lineItemDetails
     *
     * @return array
     */
    protected function separateInput(array $lineItemDetails)
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

    /**
     * @param string $itemCurrency
     *
     * @return
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function throwIfCurrencyNotSame(string $itemCurrency, $invoice)
    {
        if ($itemCurrency !== $invoice->getCurrency())
        {
            throw new Exception\BadRequestValidationFailureException(
                'Currency of all items should be same as of the invoice itself'
            );
        }
    }
}
