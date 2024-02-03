import errorService from '@razorpay/universe-utils/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { ModeT } from 'common/services/mode';
import { fetch } from 'common/services/rest/rest-fetch';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import {
  OrderCreateParams,
  OrderItem,
  OrderItemDeleteParams,
  OrderItemPatchParams,
  OrderItemsCreateParams,
  Order,
  OrderSubmitParams,
} from 'merchant/views/GCMS/Orders/types';
import { GCMS_BASE_PATH, ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import { ListApiParams, ListApiResponse, MerchantReseller } from 'merchant/views/GCMS/shared/types';

export const LIST_FETCH_BATCH_SIZE = 5;

export const fetchOrders = async ({
  mode = 'test',
  skip = 0,
  resellerName,
  orderStatus,
  fromDate,
  toDate,
}: {
  mode?: ModeT;
  skip?: number;
  resellerName?: string;
  orderStatus?: string;
  fromDate?: number;
  toDate?: number;
}) => {
  try {
    const res = await fetch<ListApiResponse<Order>>({
      url: `${GCMS_BASE_PATH}/orders${stringifyQueryParams({
        skip,
        count: LIST_FETCH_BATCH_SIZE,
        reseller_name: resellerName ?? '',
        status: !orderStatus || orderStatus === ORDERS_STATUS.all.value ? '' : orderStatus,
        from: !fromDate ? '' : fromDate,
        to: !toDate ? '' : toDate,
      })}`,
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchOrderDetails = async ({ orderId, mode = 'test' }: ListApiParams) => {
  try {
    const res = await fetch<Order>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}`,
      method: 'get',
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchOrderItems = async ({
  orderId,
  merchantId,
  mode = 'test',
}: ListApiParams): Promise<OrderItem> => {
  try {
    const res = await fetch<ListApiResponse<OrderItem>>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}/items`,
      method: 'get',
      data: {
        merchant_id: merchantId,
      },
      mode,
    });
    /* @ts-expect-error no-common-property */
    return res?.order_items;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchMerchantResellerRelationshipDetails = async ({
  resellerId,
  merchantId,
  mode = 'test',
}: ListApiParams) => {
  try {
    const res = await fetch<MerchantReseller>({
      url: `${GCMS_BASE_PATH}/merchants/${merchantId}/resellers/${resellerId}`,
      method: 'get',
      data: {
        merchant_id: merchantId,
      },
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const fetchMerchantDetails = async ({
  resellerDetailId,
  merchantId,
  mode = 'test',
}: ListApiParams) => {
  try {
    const res = await fetch<MerchantReseller>({
      url: `${GCMS_BASE_PATH}/merchant_details/${resellerDetailId}`,
      method: 'get',
      data: {
        merchant_id: merchantId,
      },
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const orderCreate = async ({ resellerId, merchantId, mode = 'test' }: OrderCreateParams) => {
  try {
    const res = await fetch<Order>({
      url: `${GCMS_BASE_PATH}/orders`,
      method: 'post',
      mode,
      data: {
        resellerId,
        merchantId,
      },
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const orderUpdate = async ({
  resellerId,
  merchantId,
  orderId,
  mode = 'test',
  order,
}: OrderCreateParams) => {
  const data = {
    reseller_id: resellerId,
    merchant_id: merchantId,
  };
  if (order?.hasOwnProperty('is_multiple_delivery')) {
    /* @ts-expect-error empty-object-key */
    data.is_multiple_delivery = order.is_multiple_delivery;
  }
  try {
    const res = await fetch<Order>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}`,
      method: 'patch',
      data,
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const orderItemsCreate = async ({
  orderId,
  merchantId,
  orderItems,
  mode = 'test',
}: OrderItemsCreateParams) => {
  try {
    const res = await fetch<ListApiResponse<OrderItem>>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}/items`,
      method: 'post',
      data: {
        merchant_id: merchantId,
        order_items: orderItems,
      },
      mode,
    });
    return res?.data;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const orderItemsPatch = async ({
  orderId,
  merchantId,
  itemId,
  orderItem,
  mode = 'test',
}: OrderItemPatchParams) => {
  try {
    const res = await fetch<ListApiResponse<OrderItem>>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}/items/${itemId}`,
      method: 'patch',
      data: {
        merchant_id: merchantId,
        ...orderItem,
      },
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const orderItemsDelete = async ({
  orderId,
  merchantId,
  itemId,
  mode = 'test',
}: OrderItemDeleteParams) => {
  try {
    const res = await fetch<Order>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}/items/${itemId}`,
      method: 'delete',
      data: {
        merchant_id: merchantId,
      },
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const orderSubmit = async ({ orderId, merchantId, mode = 'test' }: OrderSubmitParams) => {
  try {
    const res = await fetch<Order>({
      url: `${GCMS_BASE_PATH}/orders/${orderId}/submit`,
      method: 'patch',
      data: {
        merchantId,
      },
      mode,
    });
    return res;
  } catch (e: any) {
    errorService.captureError(e, {
      tags: {
        team: Teams.RAZORPAY_WALLET,
      },
      rank: Ranks.P2,
    });
    throw new Error(e?.response?.errors?.[0]);
  }
};
