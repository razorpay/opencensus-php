import { rest } from 'msw';

import { programsListResponse } from 'merchant/views/GCMS/Resellers/__tests__/mocks/fixtures';
import { getGCMSBasePath } from 'merchant/views/GCMS/shared/constants';

import {
  merchantResellersResponse,
  merchantDetailsResponse,
  orderResponse,
  merchantId,
  resellerId,
  orderId,
  processedOrderId,
  orderItemsResponse,
  resellerDetailId,
  itemId,
  ordersListResponse,
  orderEmailDeliveryStatusResponse,
  processedOrderResponse,
} from './fixtures';

export default [
  rest.get(
    `*${getGCMSBasePath()}/merchants/${merchantId}/resellers/${resellerId}`,
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(merchantResellersResponse), ctx.delay(100));
    },
  ),
  rest.get(`*${getGCMSBasePath()}/orders/${orderId}`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(orderResponse), ctx.delay(100));
  }),
  rest.get(`*${getGCMSBasePath()}/orders/${processedOrderId}`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(processedOrderResponse), ctx.delay(100));
  }),
  rest.patch(`*${getGCMSBasePath()}/orders/${orderId}`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(orderResponse), ctx.delay(100));
  }),
  rest.get(`*${getGCMSBasePath()}/merchant_details/${resellerDetailId}`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(merchantDetailsResponse), ctx.delay(100));
  }),
  rest.get(`*${getGCMSBasePath()}/orders/${orderId}/items`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(orderItemsResponse), ctx.delay(100));
  }),
  rest.patch(`*${getGCMSBasePath()}/orders/${orderId}/items/${itemId}`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(orderItemsResponse), ctx.delay(100));
  }),
  rest.get(`*${getGCMSBasePath()}/skus`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(programsListResponse), ctx.delay(100));
  }),
  rest.get(`*${getGCMSBasePath()}/orders`, (req, res, ctx) => {
    if (req.url.searchParams.get('reseller_name') === 'abc') {
      return res(
        ctx.status(200),
        ctx.json({ ...ordersListResponse, data: { ...ordersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    if (req.url.searchParams.get('id') === 'abc') {
      return res(
        ctx.status(200),
        ctx.json({ ...ordersListResponse, data: { ...ordersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    if (req.url.searchParams.get('status') === 'cancelled') {
      return res(
        ctx.status(200),
        ctx.json({ ...ordersListResponse, data: { ...ordersListResponse.data, items: [] } }),
        ctx.delay(100),
      );
    }
    return res(ctx.status(200), ctx.json(ordersListResponse), ctx.delay(100));
  }),
  rest.get(`*${getGCMSBasePath()}/orders/${orderId}/deliver/status`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(orderEmailDeliveryStatusResponse), ctx.delay(100));
  }),
];
