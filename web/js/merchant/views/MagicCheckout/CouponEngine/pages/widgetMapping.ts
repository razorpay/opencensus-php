import {
  CouponEligibilityWidget,
  CouponValidityWidget,
  DiscountDetailsWidget,
  UsageRestrictionWidget,
  ProductsPurchasedWidget,
  DiscountOfferedWidget,
  BulkDiscountOffered,
  ShippingRequirementsWidget,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon';

// widgetMappings is a mapping of coupon type to the widgets that are to be shown for that coupon type. The order of the widgets in the array is the order in which they will be shown in the UI

export const widgetMappings = {
  amount_off_products: [
    DiscountDetailsWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
  ],
  amount_off_order: [
    DiscountDetailsWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
  ],
  buyx_gety: [
    ProductsPurchasedWidget,
    DiscountOfferedWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
  ],
  bulk_order: [
    ProductsPurchasedWidget,
    BulkDiscountOffered,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
  ],
  free_shipping: [
    ShippingRequirementsWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
  ],
};
