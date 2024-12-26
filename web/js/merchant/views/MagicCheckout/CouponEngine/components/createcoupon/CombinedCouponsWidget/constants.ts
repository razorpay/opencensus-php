export const DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG = {
  amount_off_order: [
    { type: 'shouldCombineOtherAmountOffProductCoupons', name: 'Amount off product coupons' },
    { type: 'shouldCombineAmountOffOrderCoupon', name: 'Other Amount off order coupons' },
    { type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' },
  ],
  amount_off_products: [
    { type: 'shouldCombineOtherAmountOffProductCoupons', name: 'Other Amount off product coupons' },
    { type: 'shouldCombineAmountOffOrderCoupon', name: 'Amount off order coupons' },
    { type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' },
  ],
  bulk_order: [{ type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' }],
  buyx_gety: [{ type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' }],
  free_shipping: [
    { type: 'shouldCombineOtherAmountOffProductCoupons', name: 'Amount off product coupons' },
    { type: 'shouldCombineAmountOffOrderCoupon', name: 'Amount off order coupons' },
    { type: 'shouldCombineBulkDiscountCoupon', name: 'Bulk discount coupons' },
    { type: 'shouldCombineBxGyDiscountCoupon', name: 'BxGy discount coupons' },
  ],
};

export const DEFAULT_MULTI_COUPON_CONFIG = {
  amount_off_order: [{ type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' }],
  amount_off_products: [{ type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' }],
  bulk_order: [{ type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' }],
  buyx_gety: [{ type: 'shouldCombineFreeShippingCoupon', name: 'Free shipping coupons' }],
};

export const COUPON_KEYS = {
  amount_off_products: 'amount_off_products',
  free_shipping: 'free_shipping',
  bulk_order: 'bulk_order',
  buyx_gety: 'buyx_gety',
  amount_off_order: 'amount_off_order',
  freebie_item: 'freebie_item',
};
