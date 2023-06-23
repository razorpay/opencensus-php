<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

/**
 * handles all errors for Shopify
 * maintains error mapping
 */
class Errors
{

  public function getInvalidCouponApplicationResponse()
  {
    return [
        'response' => [
            'failure_code'   => 'INVALID_COUPON',
            'failure_reason' => 'Coupon not applicable',
        ],
        'status_code' => 400,
    ];
  }

  public function getMissingCouponResponse()
  {
    return [
        'response' => [
            'failure_code'   => 'INVALID_COUPON',
            'failure_reason' => 'Coupon does not exist',
        ],
        'status_code' => 400,
    ];
  }

  public function getMissingContactCouponResponse()
  {
    return [
        'response' => [
            'failure_code'   => 'LOGIN_REQUIRED',
            'failure_reason' => 'Coupon requires user to login',
        ],
        'status_code' => 400,
    ];
  }

  public function getGiftCardExpiredResponse()
  {
    return [
      'response' => [
          'failure_code'   => 'INVALID_GIFTCARD',
          'failure_reason' => 'Giftcard expired',
      ],
      'status_code' => 400,
    ];
  }

  public function getGiftCardDisabledResponse()
  {
    return [
      'response' => [
          'failure_code'   => 'INVALID_GIFTCARD',
          'failure_reason' => 'Giftcard disabled',
      ],
      'status_code' => 400,
    ];
  }

  public function getGiftCardDoesNotExistResponse()
  {
    return [
      'response' => [
          'failure_code'   => 'INVALID_GIFTCARD',
          'failure_reason' => 'Gift Card does not exist',
      ],
      'status_code' => 400,
    ];
  }

  public function getGiftCardNoBalanceResponse()
  {
    return [
      'response' => [
          'failure_code'   => 'INVALID_GIFTCARD',
          'failure_reason' => 'Balance is 0.00',
      ],
      'status_code' => 400,
    ];
  }

  public function emailRequired()
  {
    return [
      'response' => [
          'failure_code'   => 'EMAIL_REQUIRED',
          'failure_reason' => 'Email is required',
      ],
      'status_code' => 400,
    ];
  }

}
