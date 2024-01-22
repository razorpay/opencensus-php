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
} from 'merchant/views/MagicCheckout/CouponEngine/types.d';
import {
  CouponPayload,
  Condition,
  CustomerWhitelist,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/couponForm.d';

export function createFreeShippingCouponPayload({
  couponDetails,
  discountDetails,
  couponValidity,
  couponEligibility,
  usageRestriction,
  status,
  source,
  id,
}: {
  couponDetails: CouponDetails;
  discountDetails: DiscountDetails;
  couponValidity: CouponValidity;
  couponEligibility: CouponEligibility;
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
  ).toISOString();

  const expiryDate = moment(`${couponValidity.endDate} ${couponValidity.endTime}`).toISOString();

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
      },
    },
    customer_whitelist,
    disabled_methods: couponDetails.prepaidMethodsOnly ? ['cod'] : null,
  };

  return sanitizePayload(couponPayload);
}
