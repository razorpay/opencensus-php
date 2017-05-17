<?php

namespace RZP\Models\LineItem\Tax;

use RZP\Models\Base;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Tax;

class Core extends Base\Core
{
    /**
     * Creates line item taxes, given input and lineItem entity.
     *
     * @param LineItem\Entity $lineItem
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     */
    public function createLineItemTaxes(
        LineItem\Entity $lineItem,
        array $input,
        Merchant\Entity $merchant)
    {
        list($taxGroup, $taxes) = $this->getTaxGroupAndTaxes($input, $merchant);

        if ($taxes->count() === 0)
        {
            return;
        }

        $taxableAmount = Calculator::getTaxableAmountOfLineItem($lineItem, $taxes);

        foreach ($taxes as $tax)
        {
            $this->createLineItemTax($lineItem, $taxableAmount, $tax, $taxGroup);
        }
    }

    /**
     * Removes old line item taxes and creates new with given input.
     *
     * When handling updates on line item we decided we'll just delete old
     * relations and create if input contains any of the tax_id or tax_group_id.
     *
     * @param LineItem\Entity $lineItem
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     */
    public function cleanUpAndCreateLineItemTaxes(
        LineItem\Entity $lineItem,
        array $input,
        Merchant\Entity $merchant)
    {
        if ((array_key_exists(LineItem\Entity::TAX_ID, $input) === false) and
            (array_key_exists(LineItem\Entity::TAX_GROUP_ID, $input) === false))
        {
            return;
        }

        $this->cleanUpLineItemTaxes($lineItem);

        $this->createLineItemTaxes($lineItem, $input, $merchant);
    }

    /**
     * Removes all taxes for given line item.
     *
     * @param LineItem\Entity $lineItem
     *
     */
    protected function cleanUpLineItemTaxes(LineItem\Entity $lineItem)
    {
        $lineItemTaxes = $lineItem->taxes()->get();

        foreach ($lineItemTaxes as $lineItemTax)
        {
            $this->repo->deleteOrFail($lineItemTax);
        }
    }

    /**
     * Gets tax group and taxes collection from given input.
     * These are used to create line item taxes against.
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    protected function getTaxGroupAndTaxes(
        array $input,
        Merchant\Entity $merchant): array
    {
        $taxId      = $input[LineItem\Entity::TAX_ID] ?? null;
        $taxGroupId = $input[LineItem\Entity::TAX_GROUP_ID] ?? null;

        $taxGroup = null;
        $taxes = new Base\PublicCollection;

        if ($taxGroupId !== null)
        {
            $taxGroup = $this->repo->tax_group
                                   ->findByPublicIdAndMerchant(
                                        $taxGroupId,
                                        $merchant);

            $taxes = $taxGroup->taxes()->getResults();
        }
        else if ($taxId !== null)
        {
            $tax = $this->repo->tax
                              ->findByPublicIdAndMerchant($taxId, $merchant);

            $taxes->push($tax);
        }

        return [$taxGroup, $taxes];
    }

    /**
     * Creates line item tax with given tax and tax group entity.
     *
     * @param LineItem\Entity       $lineItem
     * @param int                   $taxableAmount
     * @param Tax\Entity            $tax
     * @param Tax\Group\Entity|null $taxGroup
     *
     */
    protected function createLineItemTax(
        LineItem\Entity $lineItem,
        int $taxableAmount,
        Tax\Entity $tax,
        Tax\Group\Entity $taxGroup = null)
    {
        $taxAmount = Calculator::getTaxAmount($taxableAmount, $tax);

        $input = [
            Entity::NAME       => $tax->getName(),
            Entity::RATE       => $tax->getRate(),
            Entity::RATE_TYPE  => $tax->getRateType(),
            Entity::TAX_AMOUNT => $taxAmount,
        ];

        if ($taxGroup !== null)
        {
            $input[Entity::GROUP_NAME] = $taxGroup->getName();
        }

        $entity = (new Entity)->build($input);

        // Associate the relation objects.

        if ($taxGroup !== null)
        {
            $entity->taxGroup()->associate($taxGroup);
        }

        $entity->tax()->associate($tax);
        $entity->lineItem()->associate($lineItem);

        $this->repo->saveOrFail($entity);
    }
}
