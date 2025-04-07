import { hasMultipleItems } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponPreview/helpers';
import { COUPON_NAMES } from 'merchant/views/MagicCheckout/CouponEngine/constants';
import { ModalContextValue } from 'merchant/views/MagicCheckout/CouponEngine/types';

const PRODUCT = 'product';
const PRODUCTS = 'products';
const TIMES = 'times';
const TIME = 'time';

const couponDetailsPreviewMapping = ({ widgetsData }: ModalContextValue) => ({
  title: 'Details',
  list: [
    {
      text: 'Displayed to eligible users',
      condition: () => widgetsData.couponDetails.display,
    },
    {
      text: 'Displayed when conditions are not met as unavailable',
      condition: () =>
        widgetsData.couponDetails.display && widgetsData.couponDetails.couponDiscoveryEnabled,
    },
    {
      text: 'Applicable only for Prepaid Payments',
      condition: () => widgetsData.couponDetails.prepaidMethodsOnly,
    },
    {
      text: 'Auto-applied on checkout',
      condition: () => widgetsData.couponDetails.autoapply,
    },
  ],
});

const conditionsPreviewMapping = ({ widgetsData }: ModalContextValue, couponType) => ({
  title: 'Condition',
  list: [
    {
      text: `Minimum purchase of Rs. ${widgetsData.discountDetails.minimumValue}`,
      condition: () =>
        (couponType === COUPON_NAMES.AMOUNT_OFF_ORDER ||
          couponType === COUPON_NAMES.FREE_SHIPPING) &&
        widgetsData.discountDetails.minimumType === 'min_order_value',
    },
    {
      text: `Mimimum purchase of ${widgetsData.discountDetails.minimumValue} ${
        hasMultipleItems(widgetsData.discountDetails.minimumValue) ? PRODUCTS : PRODUCT
      }`,
      condition: () =>
        (couponType === COUPON_NAMES.AMOUNT_OFF_ORDER ||
          couponType === COUPON_NAMES.FREE_SHIPPING) &&
        widgetsData.discountDetails.minimumType === 'min_qty',
    },
    {
      text: `Minimum purchase of Rs. ${widgetsData.discountDetails.minimumValue} from selected ${widgetsData.discountDetails.discountApplicableTo}`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS &&
        widgetsData.discountDetails.minimumType === 'min_order_value',
    },
    {
      text: `Mimimum purchase of ${widgetsData.discountDetails.minimumValue} ${
        hasMultipleItems(widgetsData.discountDetails.minimumValue) ? PRODUCTS : PRODUCT
      } from selected ${widgetsData.discountDetails.discountApplicableTo}`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS &&
        widgetsData.discountDetails.minimumType === 'min_qty',
    },
    {
      text: `Minimum purchase of Rs. ${widgetsData.productsPurchased.minimumValue} from selected ${widgetsData.productsPurchased.discountApplicableTo}`,
      condition: () =>
        (couponType === COUPON_NAMES.BUYX_GETY || couponType === COUPON_NAMES.FREEBIE_ITEM) &&
        widgetsData.productsPurchased.minimumType === 'min_order_value',
    },
    {
      text: `Mimimum purchase of ${widgetsData.productsPurchased.minimumValue} ${
        hasMultipleItems(widgetsData.productsPurchased.minimumValue) ? PRODUCTS : PRODUCT
      } from selected ${widgetsData.productsPurchased.discountApplicableTo}`,
      condition: () =>
        (couponType === COUPON_NAMES.BUYX_GETY ||
          couponType === COUPON_NAMES.FREEBIE_ITEM ||
          couponType === COUPON_NAMES.BULK_ORDER) &&
        widgetsData.productsPurchased.minimumType === 'min_qty',
    },
  ],
});

const discountsPreviewMapping = ({ widgetsData }: ModalContextValue, couponType) => ({
  title: 'Discount',
  list: [
    {
      text: `Rs. ${widgetsData.discountDetails.discountValue} off`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_ORDER &&
        widgetsData.discountDetails.discountType === 'fixedAmount',
    },
    {
      text: `${widgetsData.discountDetails.discountValue}% off`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_ORDER &&
        widgetsData.discountDetails.discountType === 'percentageDiscount' &&
        widgetsData.discountDetails.maxDiscountValue === '',
    },
    {
      text: `${widgetsData.discountDetails.discountValue}% off upto Rs. ${widgetsData.discountDetails.maxDiscountValue}`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_ORDER &&
        widgetsData.discountDetails.discountType === 'percentageDiscount' &&
        widgetsData.discountDetails.maxDiscountValue !== '',
    },
    {
      text: `Rs. ${widgetsData.discountDetails.discountValue} off on selected ${widgetsData.discountDetails.discountApplicableTo}`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS &&
        widgetsData.discountDetails.discountType === 'fixedAmount',
    },
    {
      text: `${widgetsData.discountDetails.discountValue}% off on selected ${widgetsData.discountDetails.discountApplicableTo}`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS &&
        widgetsData.discountDetails.discountType === 'percentageDiscount' &&
        widgetsData.discountDetails.maxDiscountValue === '',
    },
    {
      text: `${widgetsData.discountDetails.discountValue}% off upto Rs. ${widgetsData.discountDetails.maxDiscountValue} on selected ${widgetsData.discountDetails.discountApplicableTo}`,
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS &&
        widgetsData.discountDetails.discountType === 'percentageDiscount' &&
        widgetsData.discountDetails.maxDiscountValue !== '',
    },
    {
      text: `Rs. ${widgetsData.discountOffered.discountValue} off on purchase of ${
        widgetsData.discountOffered.productsOffered
      } ${
        hasMultipleItems(widgetsData.discountOffered.productsOffered) ? PRODUCTS : PRODUCT
      } from selected list`,
      condition: () =>
        couponType === COUPON_NAMES.BUYX_GETY &&
        widgetsData.discountOffered.discountSubType === 'fixedAmount' &&
        widgetsData.discountOffered.discountType === 'monetary-discount',
    },
    {
      text: `${widgetsData.discountOffered.discountValue}% off on purchase of ${
        widgetsData.discountOffered.productsOffered
      } ${
        hasMultipleItems(widgetsData.discountOffered.productsOffered) ? PRODUCTS : PRODUCT
      } from selected list`,
      condition: () =>
        couponType === COUPON_NAMES.BUYX_GETY &&
        widgetsData.discountOffered.discountSubType === 'percentageDiscount' &&
        widgetsData.discountOffered.discountType === 'monetary-discount',
    },
    {
      text: `${widgetsData.discountOffered.productsOffered} ${
        hasMultipleItems(widgetsData.discountOffered.productsOffered) ? PRODUCTS : PRODUCT
      } free from selected list`,
      condition: () =>
        couponType === COUPON_NAMES.BUYX_GETY &&
        widgetsData.discountOffered.discountType === 'free',
    },
    {
      text: '1 selected product free',
      condition: () => couponType === COUPON_NAMES.FREEBIE_ITEM,
    },
    {
      text: 'Free Shipping on the order',
      condition: () => couponType === COUPON_NAMES.FREE_SHIPPING,
    },
    {
      text: `Rs. ${widgetsData.bulkDiscountDetails.discountValue} off on purchase of selected bundle`,
      condition: () =>
        couponType === COUPON_NAMES.BULK_ORDER &&
        widgetsData.bulkDiscountDetails.discountType === 'discountOnAll' &&
        widgetsData.bulkDiscountDetails.discountSubType === 'fixedAmount',
    },
    {
      text: `${widgetsData.bulkDiscountDetails.discountValue}% on purchase of selected bundle`,
      condition: () =>
        couponType === COUPON_NAMES.BULK_ORDER &&
        widgetsData.bulkDiscountDetails.discountType === 'discountOnAll' &&
        widgetsData.bulkDiscountDetails.discountSubType === 'percentageDiscount',
    },
    {
      text: `Buy ${widgetsData.productsPurchased.minimumValue} ${
        hasMultipleItems(widgetsData.productsPurchased.minimumValue) ? PRODUCTS : PRODUCT
      } at Rs. ${widgetsData.bulkDiscountDetails.discountValue} each from selected ${
        widgetsData.productsPurchased.discountApplicableTo
      }`,
      condition: () =>
        couponType === COUPON_NAMES.BULK_ORDER &&
        widgetsData.bulkDiscountDetails.discountType === 'ratePerProduct',
    },
  ],
});

const validityPreviewMapping = ({ widgetsData }: ModalContextValue) => ({
  title: 'Validity',
  list: [
    {
      text: `Starts ${widgetsData.couponValidity.startDate} ${widgetsData.couponValidity.startTime}`,
      condition: () => widgetsData.couponValidity.startDate,
    },
    {
      text: `Expires ${widgetsData.couponValidity.endDate} ${widgetsData.couponValidity.endTime}`,
      condition: () => widgetsData.couponValidity.isLimitedUsage,
    },
    {
      text: `Doesn't Expire`,
      condition: () =>
        !widgetsData.couponValidity.isLimitedUsage && widgetsData.couponValidity.maxBudget === '',
    },
    {
      text: `Expires after Rs. ${widgetsData.couponValidity.maxBudget} budget is reached`,
      condition: () => widgetsData.couponValidity.maxBudget !== '',
    },
  ],
});

const eligibilityPreviewMapping = ({ widgetsData }: ModalContextValue) => ({
  title: 'Eligibility',
  list: [
    {
      text: 'Applies for all customers',
      condition: () => widgetsData.couponEligibility.customerGroup === 'allCustomers',
    },
    {
      text: 'Applies for specific customers',
      condition: () => widgetsData.couponEligibility.customerGroup === 'specificCustomers',
    },
  ],
});

const restrictionsPreviewMapping = ({ widgetsData }: ModalContextValue, couponType) => ({
  title: 'Restrictions',
  list: [
    {
      text: ` Applies ${widgetsData.usageRestriction.total} ${
        hasMultipleItems(widgetsData.usageRestriction.total) ? TIMES : TIME
      } in total`,
      condition: () => widgetsData.usageRestriction.isRestrictedTotalUsage,
    },
    {
      text: `Applies ${widgetsData.usageRestriction.maxUsage} ${
        hasMultipleItems(widgetsData.usageRestriction.maxUsage) ? TIMES : TIME
      } per customer`,
      condition: () => widgetsData.usageRestriction.isLimitedUsage,
    },
    {
      text: 'Applies once per order',
      condition: () =>
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS &&
        widgetsData.discountDetails.hasLimitedUseagePerOrder,
    },
    {
      text: ` ${
        Number(widgetsData.discountOffered.maxUsagePerOrder) > 1
          ? `Applies ${widgetsData.discountOffered.maxUsagePerOrder} times per order`
          : 'Applies once per order'
      }
                `,
      condition: () =>
        (couponType === COUPON_NAMES.BUYX_GETY || couponType === COUPON_NAMES.FREEBIE_ITEM) &&
        widgetsData.discountOffered.hasLimitedUseagePerOrder,
    },
    {
      text: ` ${
        Number(widgetsData.bulkDiscountDetails.maxUsagePerOrder) > 1
          ? `Applies ${widgetsData.bulkDiscountDetails.maxUsagePerOrder} times per order`
          : 'Applies once per order'
      }
                `,
      condition: () =>
        couponType === COUPON_NAMES.BULK_ORDER &&
        widgetsData.bulkDiscountDetails.hasLimitedUseagePerOrder,
    },
  ],
});

const combinationPreviewMapping = (
  { widgetsData }: ModalContextValue,
  couponType,
  isRcodEnabled,
) => ({
  title: 'Combination',
  list: [
    {
      text: `Combines with ${
        couponType === COUPON_NAMES.AMOUNT_OFF_PRODUCTS ? 'other' : ''
      } product off coupons`,
      condition: () => widgetsData.combineCoupons.shouldCombineOtherAmountOffProductCoupons,
    },
    {
      text: `Combines with ${
        couponType === COUPON_NAMES.AMOUNT_OFF_ORDER ? 'other' : ''
      } order off coupons`,
      condition: () => widgetsData.combineCoupons.shouldCombineAmountOffOrderCoupon,
    },
    {
      text: 'Combines with Free Shipping coupons',
      condition: () => !isRcodEnabled && widgetsData.combineCoupons.shouldCombineFreeShippingCoupon,
    },
    {
      text: 'Combines with bulk coupons',
      condition: () => widgetsData.combineCoupons.shouldCombineBulkDiscountCoupon,
    },
    {
      text: 'Combines with BXGY coupons',
      condition: () => widgetsData.combineCoupons.shouldCombineBxGyDiscountCoupon,
    },
    {
      text: 'Combines with Freebie Item coupons',
      condition: () => widgetsData.combineCoupons.shouldCombineFreebieItemCoupon,
    },
    {
      text: `Can't combine with other coupons`,
      condition: () =>
        isRcodEnabled &&
        (couponType === COUPON_NAMES.FREEBIE_ITEM || couponType === COUPON_NAMES.BUYX_GETY),
    },
  ],
});

const couponPreviewMapping = (
  data: ModalContextValue,
  couponType: typeof COUPON_NAMES,
  isRcodEnabled,
) => {
  return {
    couponDetails: couponDetailsPreviewMapping(data),
    conditions: conditionsPreviewMapping(data, couponType),
    discounts: discountsPreviewMapping(data, couponType),
    validity: validityPreviewMapping(data),
    eligibility: eligibilityPreviewMapping(data),
    restrictions: restrictionsPreviewMapping(data, couponType),
    combination: combinationPreviewMapping(data, couponType, isRcodEnabled),
  };
};

export default couponPreviewMapping;
