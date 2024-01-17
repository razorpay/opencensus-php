import { rest } from 'msw';

import { ProductPricingMap } from 'merchant/views/POS/types';

import {
  MOCK_ORDER_LIST,
  MOCK_LATEST_ORDER_WITH_DELIVERED_STATUS,
  MOCK_PRE_CHECKOUT_ORDER,
  MOCK_PRODUCT_PRICING_RESPONSE,
  MOCK_ORDER_RESPONSE,
  MOCK_PAID_ORDER_ITEM,
  MOCK_DELIVERED_ORDER_ITEM,
  MOCK_REJECTED_ORDER_ITEM,
  MOCK_REJECTED_WITH_REFUND_INITIATED,
  MOCK_REJECTED_WITH_REFUND_COMPLETED,
  MOCK_CMMA_CASE_CREATE_CALL,
} from './fixtures';

const delivery_available_pincode = {
  status_code: 200,
  success: true,
  data: {
    city: 'Bengaluru',
    state: 'Karnataka',
    state_code: 'KA',
  },
};

const delivery_unavailable_pincode = {
  status_code: 200,
  success: true,
  data: {
    city: 'Kamrup',
    state: 'Assam',
    state_code: 'AS',
  },
};

const pincode_error = {
  status_code: 400,
  success: false,
  errors: ['Invalid pincode'],
};

export const getPincodeInfoHandler = ({ type }: { type: string }) => {
  if (type === 'delivery_available') {
    return rest.get('*/merchant/api/*/pincodes/*', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(delivery_available_pincode), ctx.delay(50));
    });
  } else if (type === 'delivery_unavailable') {
    return rest.get('*/merchant/api/*/pincodes/*', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(delivery_unavailable_pincode), ctx.delay(50));
    });
  }
  return rest.get('*/merchant/api/*/pincodes/*', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.json(pincode_error), ctx.delay(50));
  });
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

export const commsOrderFetchHandler = () => {
  return rest.get('*/merchant/api/*/merchant/device/order/latest', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(MOCK_ORDER_RESPONSE), ctx.delay(50));
  });
};

export const getOrderDetails = ({ type }: { type: string }) => {
  const response = {
    status_code: 200,
    success: true,
    data: MOCK_PAID_ORDER_ITEM,
  };
  if (type === 'rejected') {
    response.data = MOCK_REJECTED_ORDER_ITEM;
    return rest.get('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  } else if (type === 'delivered') {
    response.data = MOCK_DELIVERED_ORDER_ITEM;
    return rest.get('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  } else if (type === 'refund_initiated') {
    response.data = MOCK_REJECTED_WITH_REFUND_INITIATED;
    return rest.get('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  } else if (type === 'refund_completed') {
    response.data = MOCK_REJECTED_WITH_REFUND_COMPLETED;
    return rest.get('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  }

  return rest.get('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};

export const updateSalePoc = ({
  isSuccess = true,
  noData = false,
}: {
  isSuccess?: boolean;
  noData?: boolean;
}) => {
  if (!isSuccess)
    return rest.patch('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
      res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: [],
        }),
        ctx.delay(50),
      ),
    );

  if (noData) {
    return rest.patch('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
      res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: null,
        }),
        ctx.delay(50),
      ),
    );
  }

  return rest.patch('*/merchant/api/*/merchant/device/*/order', (_, res, ctx) =>
    res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          order_list: [],
        },
      }),
      ctx.delay(50),
    ),
  );
};

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

export const createOrderHandler = (isSucces = true) => {
  const response = {
    status_code: 200,
    success: true,
    data: MOCK_PRE_CHECKOUT_ORDER,
  };

  const failedResponse = {
    status_code: 200,
    success: true,
  };
  if (isSucces)
    return rest.post('*/merchant/api/*/merchant/device/order', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  else
    return rest.post('*/merchant/api/*/merchant/device/order', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(failedResponse), ctx.delay(50)),
    );
};

export const getLatestOrderHandler = (type = '', isSuccess = true) => {
  if (isSuccess) {
    const response = {
      status_code: 200,
      success: true,
      data: type === 'latest_order_with_delivered' ? MOCK_LATEST_ORDER_WITH_DELIVERED_STATUS : [],
    };
    return rest.get('*/merchant/api/*/merchant/device/order/latest', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  } else {
    const response = {
      status_code: 500,
      success: false,
    };

    return rest.get('*/merchant/api/*/merchant/device/order/latest', (_, res, ctx) =>
      res(ctx.status(200), ctx.json(response), ctx.delay(50)),
    );
  }
};

export const createActvationCaseHandler = () => {
  const response = {
    status_code: 200,
    success: true,
    data: MOCK_CMMA_CASE_CREATE_CALL,
  };
  return rest.post('*/merchant/api/*/merchant/activation', (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};
