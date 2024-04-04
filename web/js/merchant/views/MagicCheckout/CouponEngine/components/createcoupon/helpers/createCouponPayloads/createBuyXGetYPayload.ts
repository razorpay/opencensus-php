import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

import { sanitizePayload } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/common';
import {
  CouponPayload,
  Condition,
  CustomerWhitelist,
  CustomerBuys,
  CartEntity,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads/couponForm.d';
import {
  CouponDetails,
  CouponValidity,
  CouponEligibility,
  DiscountOffered,
  ProductsPurchased,
  CombineCoupons,
} from 'merchant/views/MagicCheckout/CouponEngine/types.d';

export function createBuyXGetYPayload({
  couponDetails,
  productsPurchased,
  couponValidity,
  couponEligibility,
  discountOffered,
  combineCoupons,
  status,
  id,
  source,
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
}): CouponPayload {
  let customer_whitelist: CustomerWhitelist = {};
  const condition: Condition = {};
  let customerBuys: CustomerBuys = {};
  let customerGets: CustomerBuys = {};
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

  if (productsPurchased.minimumType === 'no_min_qty') {
    condition.cart = {
      ...condition.cart,
      entities: [
        {
          ...(condition.cart?.entities?.[0] ?? ({} as CartEntity)),
          total_count: {
            threshold: 1,
            op: 'gte',
          },
        },
      ],
    };
  } else {
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
  }

  if (productsPurchased.minimumType === 'min_qty') {
    redemption = {
      type: 'product_bundle',
      product_bundle: {},
    };
    if (productsPurchased.discountApplicableTo === 'products') {
      customerBuys = {
        filter: productsPurchased.discountedItemsList.map((item: string) => {
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
        filter: productsPurchased.discountedItemsList.map((item: string) => {
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

    if (discountOffered.discountApplicableTo === 'products') {
      const filter = discountOffered.discountedItemsList.map((item: string) => {
        return {
          source: 'shopify',
          filter_type: 'unique',
          unique_identifier: {
            variant_id: item,
            type: 'variant_id',
          },
        };
      });

      customerGets = {
        filter,
        qty: Number(discountOffered.productsOffered),
      };
    }

    if (discountOffered.discountApplicableTo === 'collections') {
      const filter = discountOffered.discountedItemsList.map((item: string) => {
        return {
          source: 'shopify',
          filter_type: 'collection',
          filter_value: item,
        };
      });

      customerGets = {
        filter,
        qty: Number(discountOffered.discountedItemsList.length),
        price: {
          formula: {
            coeff: 100,
            const: 0,
          },
          max_unit_discount: 0,
        },
      };
    }

    if (discountOffered.discountType === 'monetary-discount') {
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
                ? -Number(discountOffered.discountValue) * 100
                : 0,
          },
          max_unit_discount: 0,
        },
      };
    }

    if (discountOffered.discountType === 'free') {
      customerGets = {
        ...customerGets,
        price: {
          formula: {
            coeff: 0,
            const: 0,
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
        {
          filters: customerGets.filter,
          qty: Number(discountOffered.productsOffered),
        },
      ],
      price_on: [
        {
          filters: customerBuys.filter,
          price: customerBuys.price,
        },
        {
          filters: customerGets.filter,
          price: customerGets.price,
        },
      ],
      max_bundles: discountOffered.hasLimitedUseagePerOrder
        ? Number(discountOffered.maxUsagePerOrder)
        : 0,
    };
  }

  if (productsPurchased.minimumType === 'min_order_value') {
    redemption = {
      type: 'product_discount',
      product_discount: {},
    };

    if (discountOffered.discountType === 'monetary-discount') {
      redemption.product_discount = {
        unit_price: {
          formula: {
            coeff:
              discountOffered.discountSubType === 'fixedAmount'
                ? 100
                : 100 - Number(discountOffered.discountValue),
            const:
              discountOffered.discountSubType === 'fixedAmount'
                ? -Number(discountOffered.discountValue) * 100
                : 0,
          },
          max_unit_discount: 0,
        },
        max_discounted_qty: Number(discountOffered.maxUsagePerOrder),
      };
    } else {
      redemption.product_discount = {
        unit_price: {
          formula: {
            coeff: 0,
            const: 0,
          },
          max_unit_discount: 0,
        },
        max_discounted_qty: Number(discountOffered.maxUsagePerOrder),
      };
    }

    if (discountOffered.discountApplicableTo === 'products') {
      redemption.product_discount.identifiers = discountOffered.discountedItemsList.map(
        (item: string) => {
          return {
            source: 'shopify',
            filter_type: 'unique',
            unique_identifier: {
              variant_id: item,
              type: 'variant_id',
            },
          };
        },
      );
    }

    if (discountOffered.discountApplicableTo === 'collections') {
      redemption.product_discount.identifiers = discountOffered.discountedItemsList.map(
        (item: string) => {
          return {
            source: 'shopify',
            filter_type: 'collection',
            filter_value: item,
          };
        },
      );
    }
  }

  const activeDate = moment(
    `${couponValidity.startDate} ${couponValidity.startTime}`,
  ).toISOString();

  const expiryDate = moment(`${couponValidity.endDate} ${couponValidity.endTime}`).toISOString();

  const couponPayload = {
    type: 'buyx_gety',
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
    meta_data: {
      display_information: {
        couponDetails,
        productsPurchased,
        couponValidity,
        couponEligibility,
        discountOffered,
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
        type: combineCoupons.shouldCombineFreeShippingCoupon ? 'shipping_fee' : null,
      },
    ],
  };

  return sanitizePayload(couponPayload);
}
