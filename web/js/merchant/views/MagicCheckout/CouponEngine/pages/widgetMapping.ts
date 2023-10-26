import {
  CouponEligibiltyWidegt,
  CouponValidityWidget,
  DiscountDetailsWidget,
  UsageRestrictionWidget,
  ProductsPurchasedWidget,
  DiscountOfferedWidget,
  BulkDiscountOffered,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon';

// widgetMappings is a mapping of coupon type to the widgets that are to be shown for that coupon type. The order of the widgets in the array is the order in which they will be shown in the UI

export const widgetMappings = {
  amount_off_products: [
    DiscountDetailsWidget,
    CouponValidityWidget,
    CouponEligibiltyWidegt,
    UsageRestrictionWidget,
  ],
  amount_off_order: [
    DiscountDetailsWidget,
    CouponValidityWidget,
    CouponEligibiltyWidegt,
    UsageRestrictionWidget,
  ],
  buyx_gety: [
    ProductsPurchasedWidget,
    DiscountOfferedWidget,
    CouponValidityWidget,
    CouponEligibiltyWidegt,
  ],
  bulk_order: [
    ProductsPurchasedWidget,
    BulkDiscountOffered,
    CouponValidityWidget,
    CouponEligibiltyWidegt,
    UsageRestrictionWidget,
  ],
};
