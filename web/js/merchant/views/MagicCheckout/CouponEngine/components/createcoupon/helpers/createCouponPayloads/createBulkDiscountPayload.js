import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

import { sanitizePayload } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/common';

export function createBulkDiscountPayload({
  couponDetails,
  productsPurchased,
  couponValidity,
  couponEligibility,
  discountOffered,
  usageRestriction,
  combineCoupons,
  status,
  source,
  id,
}) {
  let customer_whitelist = {};
  const condition = {};
  let customerBuys = {};
  let customerGets = {};
  const redemption = {
    type: 'product_bundle',
    product_bundle: {},
  };
  const cartConditionKey =
    productsPurchased.discountApplicableTo === 'products' ? 'variant_id' : 'collection_id';

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
          values: productsPurchased.discountedItemsList,
          op: 'in',
        },
        total_count: {
          threshold: Number(productsPurchased.minimumValue),
          op: 'gte',
        },
      },
    ],
  };

  if (productsPurchased.discountApplicableTo === 'products') {
    customerBuys = {
      filter: productsPurchased.discountedItemsList.map((item) => {
        return {
          source: 'shopify',
          filter_type: 'unique',
          unique_identifier: {
            variant_id: item,
            type: 'variant_id',
          },
        };
      }),
      qty: Number(productsPurchased.minimumValue),
      price: {
        formula: {
          coeff: 100,
          const: 0,
        },
        max_unit_discount: 0,
      },
    };
  }

  if (productsPurchased.discountApplicableTo === 'collections') {
    customerBuys = {
      filter: productsPurchased.discountedItemsList.map((item) => {
        return {
          source: 'shopify',
          filter_type: 'collection',
          filter_value: item,
        };
      }),
      qty: Number(productsPurchased.minimumValue),
      price: {
        formula: {
          coeff: 100,
          const: 0,
        },
        max_unit_discount: 0,
      },
    };
  }

  if (discountOffered.discountType === 'discountOnAll') {
    customerGets = {
      ...customerGets,
      price: {
        formula: {
          coeff:
            discountOffered.discountSubType === 'fixedAmount'
              ? 100
              : 100 - Number(discountOffered.discountValue),
          const:
            discountOffered.discountSubType === 'fixedAmount'
              ? -(discountOffered.discountValue * 100)
              : 0,
        },
        max_unit_discount: 0,
      },
    };
  }

  if (discountOffered.discountType === 'ratePerProduct') {
    customerGets = {
      ...customerGets,
      price: {
        formula: {
          coeff: 0,
          const: Number(discountOffered.discountValue) * 100,
        },
        max_unit_discount: 0,
      },
    };
  }

  redemption.product_bundle = {
    bundle: [
      {
        filters: customerBuys.filter,
        qty: customerBuys.qty,
      },
    ],
    price_on: [
      {
        filters: customerBuys.filter,
        price: customerGets.price,
      },
    ],
    max_bundles: discountOffered.hasLimitedUseagePerOrder
      ? Number(discountOffered.maxUsagePerOrder)
      : 0,
  };

  const activeDate = moment(
    `${couponValidity.startDate} ${couponValidity.startTime}`,
  ).toISOString();

  const expiryDate = moment(`${couponValidity.endDate} ${couponValidity.endTime}`).toISOString();

  const couponPayload = {
    type: 'bulk_order',
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
        productsPurchased,
        couponValidity,
        couponEligibility,
        bulkDiscountDetails: discountOffered,
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
