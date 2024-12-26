import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

import { sanitizePayload } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/common';
import {
  CouponPayload,
  Condition,
  CustomerWhitelist,
  CartEntity,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/couponForm.d';
import {
  CouponDetails,
  CouponValidity,
  CouponEligibility,
  DiscountOffered,
  ProductsPurchased,
  CombineCoupons,
  UsageRestriction,
} from 'merchant/views/MagicCheckout/CouponEngine/types.d';

export function createFreebieItemPayload({
  couponDetails,
  productsPurchased,
  couponValidity,
  couponEligibility,
  discountOffered,
  combineCoupons,
  status,
  id,
  source,
  usageRestriction,
}: {
  couponDetails: CouponDetails;
  productsPurchased: ProductsPurchased;
  couponValidity: CouponValidity;
  couponEligibility: CouponEligibility;
  discountOffered: DiscountOffered;
  combineCoupons: CombineCoupons;
  status: string;
  id: string;
  source: string;
  usageRestriction: UsageRestriction;
}): CouponPayload {
  let customer_whitelist: CustomerWhitelist = {};
  const condition: Condition = {};

  let spec = {};
  let redemption: any = {};
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
      },
    ],
  };

  const minTypeKey = productsPurchased.minimumType === 'min_qty' ? 'total_count' : 'total_price';
  condition.cart = {
    ...condition.cart,
    entities: [
      {
        ...(condition.cart?.entities?.[0] ?? ({} as CartEntity)),
        [minTypeKey]: {
          threshold:
            minTypeKey === 'total_count'
              ? Number(productsPurchased.minimumValue)
              : Number(productsPurchased.minimumValue) * 100,
          op: 'gte',
        },
      },
    ],
  };

  redemption = {
    type: 'product_add',
    product_add: {},
  };

  const filters = discountOffered.discountedItemsList.map((item: string) => {
    return {
      source: 'shopify',
      filter_type: 'unique',
      unique_identifier: {
        variant_id: item,
        type: 'variant_id',
      },
    };
  });

  spec = {
    filters,
    qty: 1,
  };

  redemption.product_add = {
    spec,
    unit_price: {
      formula: {
        coeff: 0,
        const: 0,
      },
      max_unit_discount: 0,
    },
    max_discount:
      discountOffered.hasLimitedUseagePerOrder && productsPurchased.minimumType === 'min_qty'
        ? Number(discountOffered.maxUsagePerOrder)
        : 0,
  };

  const activeDate = moment(
    `${couponValidity.startDate} ${couponValidity.startTime}`,
    'YYYY-M-D h:m a',
  ).toISOString();

  const expiryDate = moment(
    `${couponValidity.endDate} ${couponValidity.endTime}`,
    'YYYY-M-D h:m a',
  ).toISOString();

  const couponPayload = {
    type: 'freebie_item',
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
        discountOffered,
        combineCoupons,
        usageRestriction,
      },
    },
    customer_whitelist,
    disabled_methods: couponDetails.prepaidMethodsOnly ? ['cod'] : null,
    flags: {
      force_display: couponDetails.display && Boolean(couponDetails.couponDiscoveryEnabled),
    },
  };

  return sanitizePayload(couponPayload);
}
