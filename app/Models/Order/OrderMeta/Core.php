<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Models\Order\OrderMeta\TaxInvoice\TaxInvoiceTransformer;

/**
 * Class Core
 * @package RZP\Models\Order\OrderMeta
 */
class Core extends Base\Core
{
    /**
     * @param Order\Entity $order
     * @param array        $input
     *
     * @return Entity|null
     */
    public function createAndSaveOrderMeta(Order\Entity $order, array $input)
    {
        return $this->createAndSaveTaxInvoice($order, $input);
    }

    /**
     * @param Order\Entity $order
     * @param array        $input
     *
     * @return Entity|null
     */
    protected function createAndSaveTaxInvoice(Order\Entity $order, array & $input)
    {
        if (isset($input[Type::TAX_INVOICE]) === false)
        {
            return;
        }

        $taxInvoiceTransformer = new TaxInvoiceTransformer($order, $input[Type::TAX_INVOICE]);

        if ($taxInvoiceTransformer->preProcess() === false)
        {
            $this->trace->info(
                TraceCode::ORDER_META_TAX_INVOICE_NON_GST_FLOW,
                [
                    'order_id' => $order->getId(),
                    'input'    => $input[Type::TAX_INVOICE],
                ]);

            return;
        }

        $taxInvoice = $taxInvoiceTransformer->transform();

        $orderMetaInput = [
            Entity::ORDER_ID    => $order->getId(),
            Entity::TYPE        => Order\Entity::TAX_INVOICE,
            Entity::VALUE       => $taxInvoice,
        ];

        $orderMeta = $this->saveOrderMeta($orderMetaInput);

        $input[Type::TAX_INVOICE] = $taxInvoice;

        return $orderMeta;
    }

    /**
     * @param array $orderMetaInput
     *
     * @return Entity
     */
    protected function saveOrderMeta(array $orderMetaInput): Entity
    {
        $orderMeta = (new Entity)->build($orderMetaInput);

        $this->repo->order_meta->saveOrFail($orderMeta);

        $this->trace->info(
            TraceCode::ORDER_META_CREATED,
            [
                'order_meta' => $orderMeta->toArrayTrace(),
            ]);

        return $orderMeta;
    }
}

