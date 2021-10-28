<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Trace\Tracer;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink;
use RZP\Models\Currency\Currency;

class Core extends Base\Core
{
    public function create(
        array $input,
        Merchant\Entity $merchant,
        $paymentLink
    ): Entity
    {
        $paymentPageItem = (new Entity)->generateId();

        $paymentPageItem->merchant()->associate($merchant);

        $paymentPageItem->paymentLink()->associate($paymentLink);

        $this->stripSignsIfNecessary($input);

        $paymentPageItem->build($input);

        $this->addItemAsRequired($input, $merchant, $paymentLink, $paymentPageItem);

        $this->upsertSettings($paymentPageItem, $input[Entity::SETTINGS] ?? []);

        $this->modifyDataIfRequired($paymentPageItem, $input);

        $this->repo->saveOrFail($paymentPageItem);

        $this->repo->loadRelations($paymentPageItem);

        return $paymentPageItem;
    }

    protected function addItemAsRequired(
        array & $input,
        Merchant\Entity $merchant,
        $paymentLink,
        Entity & $paymentPageItem)
    {
        $validator = new Validator;

        $validator->validateItemPresent($input, $paymentPageItem);

        if ($paymentPageItem->doesPlanExists() === true)
        {
            $planDetails = $this->getItemAndPlanDetailsFromPlan($merchant, $paymentPageItem);

            $item = $planDetails[0];

            $input[Entity::ITEM] = $item;

            $input[Entity::PRODUCT_CONFIG]['plan_details'] = $planDetails[1];
        }

        $item = (new Item\Core)->getOrCreateItemForType(
            $input,
            $merchant,
            Item\Type::PAYMENT_PAGE
        );

        $validator->validateItemCurrency($item, $paymentLink);

        $paymentPageItem->item()->associate($item);
    }

    protected function getItemAndPlanDetailsFromPlan(
        Merchant\Entity $merchant,
        Entity & $paymentPageItem)
    {
        $planId = $paymentPageItem->getPlanId();

        $responseJson = $this->app['module']->subscription->fetchPlan('plan_'.$planId, $merchant);

        $itemFromPlan = $responseJson[Entity::ITEM];

        $itemInput = [
            Item\Entity::NAME           => $itemFromPlan[Item\Entity::NAME],
            Item\Entity::AMOUNT         => $itemFromPlan[Item\Entity::AMOUNT],
            Item\Entity::CURRENCY       => $itemFromPlan[Item\Entity::CURRENCY],
            Item\Entity::DESCRIPTION    => $itemFromPlan[Item\Entity::DESCRIPTION],
        ];

        $planDetails = [
            'interval' => $responseJson['interval'],
            'period'   => $responseJson['period'],
        ];

        return [$itemInput, $planDetails];
    }

    protected function modifyDataIfRequired(Entity & $paymentPageItem, array $input)
    {
        if (isset($input[Entity::PRODUCT_CONFIG]) === true)
        {
            $paymentPageItem->setProductConfig(json_encode($input[Entity::PRODUCT_CONFIG]));
        }
    }

    public function stripSignsIfNecessary(& $input)
    {
       if (isset($input[Entity::PLAN_ID]) === true)
       {
           $planId = PaymentLink\Entity::stripDefaultSign($input['plan_id']);

           $input[Entity::PLAN_ID] = $planId;
       }
    }

    public function fetch(string $id, Merchant\Entity $merchant)
    {
        $paymentPageItem = $this->repo->payment_page_item->findByPublicIdAndMerchant($id, $merchant);

        return $paymentPageItem;
    }

    public function createMany(
        array $input,
        Merchant\Entity $merchant,
        PaymentLink\Entity $paymentLink
    ): array
    {
        (new Validator)->validateInput(
            'create_many',
            [Entity::PAYMENT_PAGE_ITEMS => $input]
        );

        $paymentPageItems = [];

        $this->repo->transaction(
            function() use ($merchant, $paymentLink, $input, & $paymentPageItems)
            {
                foreach ($input as $paymentPageItem)
                {
                    $paymentPageItems[] = $this->create(
                        $paymentPageItem,
                        $merchant,
                        $paymentLink
                    );
                }
            }
        );

        return $paymentPageItems;
    }

    public function update(Entity $paymentPageItem, array $input)
    {
        Tracer::inSpan(['name' => 'payment_page.ppi.update.validate'], function() use($input, $paymentPageItem)
        {
            $paymentPageItem->getValidator()->validateInputForUpdate($input);
        });

        if (isset($input[Entity::ITEM]) === true)
        {
            Tracer::inSpan(['name' => 'payment_page.ppi.update.item'], function() use($paymentPageItem,$input)
            {
                (new Item\Core)->update(
                    $paymentPageItem->item,
                    $input[Entity::ITEM],
                    $this->merchant
                );
            });
        }

        $paymentPageItem->edit($input);

        Tracer::inSpan(['name' => 'payment_page.ppi.update.upsert'], function() use($paymentPageItem, $input)
        {
            $this->upsertSettings($paymentPageItem, $input[Entity::SETTINGS] ?? []);
        });

        $this->repo->saveOrFail($paymentPageItem);

        return $paymentPageItem;
    }

    public function updatePaymentPageItemsAsPut(
        array $paymentPageItemsDetails,
        Merchant\Entity $merchant,
        PaymentLink\Entity $paymentLink)
    {
        (new Validator)->validateUpdatePaymentPageItems($paymentPageItemsDetails);

        Tracer::inSpan(['name' => 'payment_page.delete.ppi_via_update'], function() use($paymentLink, $paymentPageItemsDetails)
        {
            $this->deletePaymentPageItemsViaUpdate($paymentLink, $paymentPageItemsDetails);
        });

        Tracer::inSpan(['name' => 'payment_page.create_or_update_ppi_via_update'], function() use($paymentPageItemsDetails, $paymentLink, $merchant)
        {
            $this->createOrUpdatePaymentPageItemsViaUpdate(
                $paymentPageItemsDetails,
                $paymentLink,
                $merchant
            );
        });
    }

    public function delete(Entity $paymentPageItem)
    {
        return $this->repo->line_item->deleteOrFail($paymentPageItem);
    }

    protected function deletePaymentPageItemsViaUpdate(
        PaymentLink\Entity $paymentLink,
        array $paymentPageItemsDetails)
    {
        $existingPaymentPageItems = $paymentLink->paymentPageItems()->get();

        $inputPaymentPageItemIds = collect($paymentPageItemsDetails)->pluck('id')->all();

        $existingPaymentPageItems->map(
            function($existingPaymentPageItem, $i) use ($inputPaymentPageItemIds, $paymentLink)
            {
                if (in_array(
                        $existingPaymentPageItem->getPublicId(),
                        $inputPaymentPageItemIds,
                        true) === false)
                {
                    $this->delete($existingPaymentPageItem);
                }
            }
        );
    }

    protected function createOrUpdatePaymentPageItemsViaUpdate(
        array $paymentPageItemsDetails,
        PaymentLink\Entity $paymentLink,
        Merchant\Entity $merchant)
    {
        foreach ($paymentPageItemsDetails as $paymentPageItemDetails)
        {
            if (isset($paymentPageItemDetails[Entity::ID]) === true)
            {
                $paymentPageItemId = $paymentPageItemDetails[Entity::ID];

                unset($paymentPageItemDetails[Entity::ID]);

                $paymentPageItemId = Entity::verifyIdAndStripSign($paymentPageItemId);

                $paymentPageItem = $this->repo->payment_page_item
                                       ->findByIdAndPaymentLinkEntityOrFail(
                                           $paymentPageItemId,
                                           $paymentLink
                                       );

                $this->update(
                    $paymentPageItem,
                    $paymentPageItemDetails
                );
            }
            else
            {
                $this->create($paymentPageItemDetails, $merchant, $paymentLink);
            }
        }
    }

    /**
     * Every payment page item could have set of setting associated. Ref: Model\Settings.
     * @param  Entity $paymentPageItem
     * @param  array  $settings
     */
    protected function upsertSettings(Entity $paymentPageItem, array $settings)
    {
        if (empty($settings) === false)
        {
            $paymentPageItem->getSettingsAccessor()->upsert($settings)->save();
        }
    }
}
