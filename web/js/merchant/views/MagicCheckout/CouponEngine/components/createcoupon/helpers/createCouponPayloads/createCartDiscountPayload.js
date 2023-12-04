import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

import { sanitizePayload } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/common';

export function createCartDiscountPayload({
  couponDetails,
  discountDetails,
  couponValidity,
  couponEligibility,
  usageRestriction,
  status,
  source,
  id,
}) {
  const condition = {};
  let customer_whitelist = {};

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

  const couponPayload = {
    type: 'amount_off_order',
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
          type: 'discount_cart',
          cart_discount: {
            price: {
              formula: {
                coeff:
                  discountDetails.discountType === 'fixedAmount'
                    ? 100
                    : 100 - Number(discountDetails.discountValue),
                const:
                  discountDetails.discountType === 'fixedAmount'
                    ? -Number(discountDetails.discountValue) * 100
                    : 0,
              },
              max_unit_discount: Number(discountDetails.maxDiscountValue) * 100,
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
  };

  return sanitizePayload(couponPayload);
}
