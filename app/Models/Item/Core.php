<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::ITEM_CREATE_REQUEST,
            $input
        );

        $this->modifyInputToHandleRenamedAttributes($input);

        $item = (new Entity)->build($input);

        $item->merchant()->associate($merchant);

        $this->handleTaxInputs($item, $input, $merchant);

        $this->repo->saveOrFail($item);

        return $item;
    }

    public function update(Entity $item, array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::ITEM_UPDATE_REQUEST,
            [
                'item_id' => $item->getId(),
                'input'   => $input,
            ]);

        $item->getValidator()->validateUpdateOperation($item);

        $this->modifyInputToHandleRenamedAttributes($input);

        $item->edit($input);

        $this->handleTaxInputs($item, $input, $merchant);

        $this->repo->saveOrFail($item);

        return $item;
    }

    public function delete(Entity $item)
    {
        $this->trace->info(
            TraceCode::ITEM_DELETE_REQUEST,
            [
                'item_id' => $item->getId(),
            ]);

        $item->getValidator()->validateDeleteOperation($item);

        return $this->repo->item->deleteOrFail($item);
    }

    public function getOrCreateItemForType(
        array $input,
        Merchant\Entity $merchant,
        string $type): Entity
    {
        if (empty($input[Entity::ITEM_ID]) === false)
        {
            $item = $this->repo->item->findByPublicIdAndMerchantForType(
                                                        $input[Entity::ITEM_ID],
                                                        $merchant,
                                                        $type);
        }
        else
        {
            $itemInput = $input[Entity::ITEM];

            $itemInput[Entity::TYPE] = $type;

            $item = $this->create($itemInput, $merchant);
        }

        return $item;
    }

    /**
     * Handles tax inputs. Item's API can receive tax_id and(or) tax_group_id.
     * This method consumes those keys and associate/dissociate relations as
     * needed.
     *
     * @param Entity          $item
     * @param array           $input
     * @param Merchant\Entity $merchant
     */
    private function handleTaxInputs(
        Entity $item,
        array $input,
        Merchant\Entity $merchant)
    {
        // Handles tax_id

        if (array_key_exists(Entity::TAX_ID, $input) === true)
        {
            $taxId = $input[Entity::TAX_ID];

            if (empty($taxId) === true)
            {
                $item->tax()->dissociate();
            }
            else
            {
                $tax = $this->repo->tax
                                  ->findByPublicIdAndMerchant($taxId, $merchant);

                $item->tax()->associate($tax);
            }
        }

        // Handles tax_group_id

        if (array_key_exists(Entity::TAX_GROUP_ID, $input) === true)
        {
            $taxGroupId = $input[Entity::TAX_GROUP_ID];

            if (empty($taxGroupId) === true)
            {
                $item->taxGroup()->dissociate();
            }
            else
            {
                $taxGroup = $this->repo->tax_group
                                       ->findByPublicIdAndMerchant(
                                            $taxGroupId, $merchant);

                $item->taxGroup()->associate($taxGroup);
            }
        }
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
}
