import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

// helper imports
import { sanitizePayload } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/common';

// type imports
import {
  CouponDetails,
  DiscountDetails,
  CouponValidity,
  CouponEligibility,
  UsageRestriction,
  CombineCoupons,
} from 'merchant/views/MagicCheckout/CouponEngine/types.d';
import {
  CouponPayload,
  Condition,
  CustomerWhitelist,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/couponForm.d';

//constant imports
import { COUPON_KEYS } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CombinedCouponsWidget/constants';

export function createFreeShippingCouponPayload({
  couponDetails,
  discountDetails,
  couponValidity,
  couponEligibility,
  combineCoupons,
  usageRestriction,
  status,
  source,
  id,
}: {
  couponDetails: CouponDetails;
  discountDetails: DiscountDetails;
  couponValidity: CouponValidity;
  couponEligibility: CouponEligibility;
  combineCoupons: CombineCoupons;
  usageRestriction: UsageRestriction;
  status: string;
  source: string;
  id: string;
}): CouponPayload {
  const condition: Condition = {};
  let customer_whitelist: CustomerWhitelist = {};

  if (
    couponEligibility.customerGroup === 'specificCustomers' &&
    couponEligibility.customerDetailsType
  ) {
    customer_whitelist = {
      key: couponEligibility.customerDetailsType === 'phone_number' ? 'contact' : 'email',
      segment_ids: couponEligibility.customerList,
    };
  }

  if (discountDetails.minimumType !== 'no_min_qty') {
    const minTypeKey = discountDetails.minimumType === 'min_qty' ? 'quantity' : 'amount';
    condition.cart = {
      [minTypeKey]: {
        threshold:
          minTypeKey === 'amount'
            ? Number(discountDetails.minimumValue) * 100
            : Number(discountDetails.minimumValue),
        op: 'gte',
      },
    };
  }
  const activeDate = moment(
    `${couponValidity.startDate} ${couponValidity.startTime}`,
    'YYYY-M-D h:m a',
  ).toISOString();

  const expiryDate = moment(
    `${couponValidity.endDate} ${couponValidity.endTime}`,
    'YYYY-M-D h:m a',
  ).toISOString();

  const couponPayload: CouponPayload = {
    type: 'free_shipping',
    code: couponDetails.code,
    description: couponDetails.description,
    display: couponDetails.display,
    auto_apply: couponDetails.autoapply,
    currency: 'INR',
    active: activeDate,
    expiry: couponValidity.isLimitedUsage ? expiryDate : null,
    budget: Number(couponValidity.maxBudget) * 100,
    status,
    source,
    id,
    discover_rules: isEmpty(condition) ? null : [condition],
    evaluate_rules: [
      {
        condition,
        redemption: {
          type: 'discount_fee',
          fee_discount: {
            type: 'shipping',
            price: {
              formula: {
                coeff: 0,
                const: 0,
              },
              max_unit_discount: 0,
            },
          },
        },
      },
    ],
    usage: {
      customer_email:
        usageRestriction.isLimitedUsage && usageRestriction.limitBy === 'email'
          ? Number(usageRestriction.maxUsage)
          : null,
      customer_mobile_no:
        usageRestriction.isLimitedUsage && usageRestriction.limitBy === 'phone'
          ? Number(usageRestriction.maxUsage)
          : null,
      total: usageRestriction.isRestrictedTotalUsage ? Number(usageRestriction.total) : null,
    },
    meta_data: {
      display_information: {
        couponDetails,
        discountDetails,
        couponValidity,
        couponEligibility,
        usageRestriction,
        combineCoupons,
      },
    },
    customer_whitelist,
    disabled_methods: couponDetails.prepaidMethodsOnly ? ['cod'] : null,
    flags: {
      force_display: couponDetails.display && Boolean(couponDetails.couponDiscoveryEnabled),
    },
    combined_coupons: [
      {
        type: combineCoupons.shouldCombineAmountOffOrderCoupon
          ? COUPON_KEYS.amount_off_order
          : null,
      },
      {
        type: combineCoupons.shouldCombineOtherAmountOffProductCoupons
          ? COUPON_KEYS.amount_off_products
          : null,
      },
      {
        type: combineCoupons.shouldCombineBxGyDiscountCoupon ? COUPON_KEYS.buyx_gety : null,
      },
      {
        type: combineCoupons.shouldCombineBulkDiscountCoupon ? COUPON_KEYS.bulk_order : null,
      },
      {
        type: combineCoupons.shouldCombineFreebieItemCoupon ? COUPON_KEYS.freebie_item : null,
      },
    ],
  };

  return sanitizePayload(couponPayload);
}
