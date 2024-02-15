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
  OrderUpdateParams,
} from 'merchant/views/GCMS/Orders/types';
import { getGCMSBasePath, ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import { ListApiParams, ListApiResponse, MerchantReseller } from 'merchant/views/GCMS/shared/types';

export const LIST_FETCH_BATCH_SIZE = 25;

export const fetchOrders = async ({
  mode = 'test',
  skip = 0,
  resellerName,
  orderStatus,
  fromDate,
  toDate,
  orderId,
}: {
  mode?: ModeT;
  skip?: number;
  resellerName?: string;
  orderStatus?: string;
  fromDate?: number;
  toDate?: number;
  orderId?: string;
}) => {
  try {
    const res = await fetch<ListApiResponse<Order>>({
      url: `${getGCMSBasePath(mode)}/orders${stringifyQueryParams({
        skip,
        count: LIST_FETCH_BATCH_SIZE,
        reseller_name: resellerName ?? '',
        id: orderId ?? '',
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
      url: `${getGCMSBasePath(mode)}/orders/${orderId}`,
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
      url: `${getGCMSBasePath(mode)}/orders/${orderId}/items`,
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
      url: `${getGCMSBasePath(mode)}/merchants/${merchantId}/resellers/${resellerId}`,
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
      url: `${getGCMSBasePath(mode)}/merchant_details/${resellerDetailId}`,
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
      url: `${getGCMSBasePath(mode)}/orders`,
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
  status,
}: OrderUpdateParams) => {
  const data = {
    reseller_id: resellerId,
    merchant_id: merchantId,
  };
  if (order?.hasOwnProperty('is_multiple_delivery')) {
    /* @ts-expect-error empty-object-key */
    data.is_multiple_delivery = order.is_multiple_delivery;
  }
  if (status) {
    /* @ts-expect-error empty-object-key */
    data.status = status;
  }
  try {
    const res = await fetch<Order>({
      url: `${getGCMSBasePath(mode)}/orders/${orderId}`,
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
      url: `${getGCMSBasePath(mode)}/orders/${orderId}/items`,
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
      url: `${getGCMSBasePath(mode)}/orders/${orderId}/items/${itemId}`,
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
      url: `${getGCMSBasePath(mode)}/orders/${orderId}/items/${itemId}`,
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
    const data = {
      merchantId,
    };
    const res = await fetch<Order>({
      url: `${getGCMSBasePath(mode)}/orders/${orderId}/submit`,
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

export const fetchResellerOrders = async ({
  mode = 'test',
  skip = 0,
  orderStatus,
  fromDate,
  toDate,
  resellerId,
  orderId,
}: {
  mode?: ModeT;
  skip?: number;
  orderStatus?: string;
  fromDate?: number;
  toDate?: number;
  resellerId?: string;
  orderId: string;
}) => {
  try {
    const res = await fetch<ListApiResponse<Order>>({
      url: `${getGCMSBasePath(mode)}/orders${stringifyQueryParams({
        reseller_id: resellerId,
        id: orderId,
        skip,
        count: LIST_FETCH_BATCH_SIZE,
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
