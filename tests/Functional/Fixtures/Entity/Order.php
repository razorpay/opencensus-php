<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Illuminate\Support\Facades\App;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as Fields;

class Order extends Base
{
    public function createTpvOrder(array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'               => '10000000000000',
            'account_number'            => '0001231321321',
            'bank'                      => 'ICIC',
            'method'                    => 'netbanking',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createPaymentCaptureOrder(array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'               => '10000000000000',
            'receipt'                   => 'test_auto_capture_receipt',
            'currency'                  => 'INR',
            'amount'                    => 1000,
            'payment_capture'           => true,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createWithOffers($offers, array $attributes = [])
    {
        $defaultValues = [
            'merchant_id' => '10000000000000',
            'currency'    => 'INR',
            'amount'      => 100000,
            'force_offer' => false,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $order = parent::create($attributes);

        $offers = is_array($offers) ? $offers : [$offers];

        foreach ($offers as $offer)
        {
            $this->fixtures->create('entity_offer', [
                'entity_id'   => $order->getId(),
                'entity_type' => 'order',
                'offer_id'    => $offer->getId(),
            ]);
        }

        return $order;
    }

    public function createWithUndiscountedOffers($offers, array $attributes = [])
    {
        return $this->createWithOffers($offers, $attributes);
    }

    public function createWithUndiscountedOfferApplied(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createWithOfferApplied(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
            'discount'                  => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    /**
     * @param array $taxInvoice
     * @param array $attributes
     *
     * @return array|mixed
     */
    public function createOrderWithTaxInvoice(array $taxInvoice, array $attributes = [])
    {
        $order = parent::create($attributes);

        $this->fixtures->create('order_meta', [
            'order_id' => $order->getId(),
            'type'     => 'tax_invoice',
            'value'    => $taxInvoice,
        ]);

        return $order;
    }

    public function createEmandateOrder(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'      => '10000000000000',
            'receipt'          => 'test_tpv_receipt',
            'currency'         => 'INR',
            'method'           => 'emandate',
            'amount'           => 100000,
            'payment_capture'  => true
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createWalletInternationalOrder(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'      => '10000000000000',
            'receipt'          => 'test_tpv_receipt',
            'currency'         => 'USD',
            'method'           => 'wallet',
            'amount'           => 100000,
            'payment_capture'  => true
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    /**
     * Creates a paid order.
     * If not passed, usage default amount value as amount_paid and 'paid' as
     * status.
     *
     * @param array $attributes
     *
     * @return OrderEntity
     */
    public function createPaid(array $attributes = []): OrderEntity
    {
        $amountPaid = $attributes['amount_paid'] ?? 1000000;
        $status     = $attributes['status'] ?? 'paid';

        $attributes = array_merge(
                        $attributes,
                        [
                            'amount_paid' => $amountPaid,
                            'status'      => $status,
                        ]);

        return parent::create($attributes);
    }

    public function create1ccOrderWithLineItems(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id' => '10000000000000',
            'receipt'     => 'test_tpv_receipt',
            'currency'    => 'INR',
            'amount'      => 100000,
            'discount'    => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $customerDetails = array_get($attributes, 'customer_details', []);

        unset($attributes['customer_details']);

        $order = parent::create($attributes);

        $app = App::getFacadeRoot();

        $orderMetaAttributes = [
            'order_id' => $order->getId(),
            'type'     => 'one_click_checkout',
            'value'    => [
                'line_items'       => [
                    [
                        'name'        => 'Test Line Item',
                        'description' => 'Test Line Item Description',
                        'price'       => 100000,
                        'quantity'    => 1,
                    ],
                ],
                Fields::SHIPPING_FEE => 0,
                Fields::COD_FEE => 0,
                'line_items_total' => 100000,
                'customer_details' => $app['encrypter']->encrypt($customerDetails)
            ],
        ];

        $this->fixtures->create('order_meta', $orderMetaAttributes);

        return $order;
    }
}
