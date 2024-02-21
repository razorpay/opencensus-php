import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

import { sanitizePayload } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/common';

export function createProductDiscountPayload({
  couponDetails,
  discountDetails,
  couponValidity,
  couponEligibility,
  usageRestriction,
  combineCoupons,
  status,
  source,
  id,
}) {
  let customer_whitelist = {};
  const condition = {};
  const redemption = {
    type: 'product_discount',
    product_discount: {},
  };
  const cartConditionKey =
    discountDetails.discountApplicableTo === 'products' ? 'variant_id' : 'collection_id';

  if (
    couponEligibility.customerGroup === 'specificCustomers' &&
    couponEligibility.customerDetailsType
  ) {
    customer_whitelist = {
      key: couponEligibility.customerDetailsType === 'phone_number' ? 'contact' : 'email',
      segment_ids: couponEligibility.customerList,
    };
  }

  condition.cart = {
    entities: [
      {
        key: cartConditionKey,
        condition: {
          values: discountDetails.discountedItemsList,
          op: 'in',
        },
      },
    ],
  };

  if (discountDetails.minimumType === 'no_min_qty') {
    condition.cart = {
      ...condition.cart,
      entities: [
        {
          ...condition.cart?.entities?.[0],
          total_count: {
            threshold: 1,
            op: 'gte',
          },
        },
      ],
    };
  } else {
    const minTypeKey = discountDetails.minimumType === 'min_qty' ? 'total_count' : 'total_price';
    condition.cart = {
      ...condition.cart,
      entities: [
        {
          ...condition.cart?.entities?.[0],
          [minTypeKey]: {
            threshold:
              minTypeKey === 'total_count'
                ? Number(discountDetails.minimumValue)
                : Number(discountDetails.minimumValue) * 100,
            op: 'gte',
          },
        },
      ],
    };
  }

  redemption.product_discount = {
    unit_price: {
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
      max_unit_discount: 0,
    },
    max_discount: Number(discountDetails.maxDiscountValue) * 100,
  };

  if (discountDetails.discountApplicableTo === 'products') {
    redemption.product_discount.identifiers = discountDetails.discountedItemsList.map((item) => {
      return {
        source: 'shopify',
        filter_type: 'unique',
        unique_identifier: {
          variant_id: item,
          type: 'variant_id',
        },
      };
    });
  }

  if (discountDetails.discountApplicableTo === 'collections') {
    redemption.product_discount.identifiers = discountDetails.discountedItemsList.map((item) => {
      return {
        source: 'shopify',
        filter_type: 'collection',
        filter_value: item,
      };
    });
  }

  const activeDate = moment(
    `${couponValidity.startDate} ${couponValidity.startTime}`,
  ).toISOString();

  const expiryDate = moment(`${couponValidity.endDate} ${couponValidity.endTime}`).toISOString();

  const couponPayload = {
    type: 'amount_off_products',
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
        redemption,
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
      force_display: couponDetails.display && couponDetails.couponDiscoveryEnabled,
    },
    combined_coupons: [
      {
        type: combineCoupons.shouldCombineFreeShippingCoupon ? 'shipping_fee' : null,
      },
    ],
  };

  return sanitizePayload(couponPayload);
}
