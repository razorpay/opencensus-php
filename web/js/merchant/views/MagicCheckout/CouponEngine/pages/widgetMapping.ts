import {
  CouponEligibilityWidget,
  CouponValidityWidget,
  DiscountDetailsWidget,
  UsageRestrictionWidget,
  ProductsPurchasedWidget,
  DiscountOfferedWidget,
  BulkDiscountOffered,
  ShippingRequirementsWidget,
  CombineCouponsWidget,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon';

// widgetMappings is a mapping of coupon type to the widgets that are to be shown for that coupon type. The order of the widgets in the array is the order in which they will be shown in the UI

export const widgetMappings = {
  amount_off_products: [
    DiscountDetailsWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
    CombineCouponsWidget,
  ],
  amount_off_order: [
    DiscountDetailsWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
    CombineCouponsWidget,
  ],
  buyx_gety: [
    ProductsPurchasedWidget,
    DiscountOfferedWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    CombineCouponsWidget,
  ],
  bulk_order: [
    ProductsPurchasedWidget,
    BulkDiscountOffered,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
    CombineCouponsWidget,
  ],
  free_shipping: [
    ShippingRequirementsWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
    CombineCouponsWidget,
  ],
  freebie_item: [
    ProductsPurchasedWidget,
    DiscountOfferedWidget,
    CouponValidityWidget,
    CouponEligibilityWidget,
    UsageRestrictionWidget,
  ],
};
