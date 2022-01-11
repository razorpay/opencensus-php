<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

/**
 * util functions for shopify
 */
class Utils
{

  public function getLineItemsFromCart(array $cart): array
  {
      $items = $cart['items'];
      $lineItems = array();
      if (empty($items))
      {
          // TODO: cart is empty, now what?
      }

      foreach ($items as $item)
      {
          $lineItems[] = [
            'variant_id' => $item['variant_id'],
            'quantity'   => $item['quantity']
          ];
      }

      return $lineItems;
  }

  public function convertToGraphqlId(array $items): array
  {
      $lineItems = array();

      foreach ($items as $item)
      {
          $lineItems[] = [
            'variantId' => $this->convertToBase64($item['variant_id'], 'variant'),
            'quantity'   => $item['quantity']
          ];
      }
      return ['lineItems' => $lineItems];
  }

  public function convertToBase64(string $id, string $type): string
  {
      switch ($type)
      {
          case 'product':
              $id = 'gid://shopify/Product/' . $id;
              break;

          case 'variant':
              $id = 'gid://shopify/ProductVariant/' . $id;
              break;

          case 'checkout':
              $id = 'gid://shopify/Checkout/' . $id;
              break;
      }
      return base64_encode($id);
  }

  public function getInvalidCouponResponse()
  {
    return [
        'response' => [
            'failure_code' => 'INVALID_COUPON',
            'failure_reason' => 'Coupon not applicable',
        ],
        'status_code' => 400,
    ];
  }

  public function formatNumber($num, $decimals = 2)
  {
    return number_format($num, $decimals, '.', '');
  }

}
