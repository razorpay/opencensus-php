import { rest } from 'msw';
import { MOCK_ORDER_LIST, MOCK_PRODUCT_PRICING_RESPONSE } from './fixtures';

export type PricingTypes = 'monthly' | 'lifetime' | 'free';
export type PlanType = {
  one_time_charge: number;
  plan_name: PricingTypes;
  rental_charges: number;
  setup_fee: number;
};

export type RateConfig = {
  advanced_rental_periods: number;
  rental_discount_periods: number;
  paper_roll_charges: number;
  plans: PlanType[];
};

export type DeviceMetaData = {
  charge_collection_product_id: string;
  rate_config_v2?: RateConfig | null;
};
export type ProductPricingMap = {
  name: string;
  code: string;
  entity_type?: string;
  rate_config: {
    monthly: number;
    lifetime: number;
    setup_fee: number;
  };
  metadata?: DeviceMetaData;
}[];

export const getOrdersListHandler = (isEmptyList = false) => {
  const response = {
    status_code: 200,
    success: true,
    data: {
      order_list: isEmptyList ? [] : MOCK_ORDER_LIST,
    },
  };
  return rest.get('*/merchant/api/*/merchant/device/order', (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};

export const getProductPricingHandler = (customProductPricing?: ProductPricingMap) => {
  const response = {
    status_code: 200,
    success: true,
    data: {
      rzp_key: 'rzp_test_mockKey',
      configs: [...MOCK_PRODUCT_PRICING_RESPONSE, ...(customProductPricing || [])],
    },
  };
  return rest.get('*/merchant/api/*/merchant/device_config', (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};

export const getSubmerchantOrdersListHandler = (isEmptyList = false) => {
  const response = {
    status_code: 200,
    success: true,
    data: {
      order_list: isEmptyList ? [] : MOCK_ORDER_LIST,
    },
  };
  return rest.get('*/merchant/api/*/submerchants/:submerchantId/device/order', (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};

export const getSubmerchantProductPricingHandler = (customProductPricing?: ProductPricingMap) => {
  const response = {
    status_code: 200,
    success: true,
    data: {
      rzp_key: 'rzp_test_mockKey',
      configs: [...MOCK_PRODUCT_PRICING_RESPONSE, ...(customProductPricing || [])],
    },
  };
  console.log('response', response);
  return rest.get('*/merchant/api/:mode/submerchants/:submerchantId/device_config', (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};
