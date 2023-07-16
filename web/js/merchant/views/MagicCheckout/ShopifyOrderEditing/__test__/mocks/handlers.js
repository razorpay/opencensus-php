// This file be used - once the api integration is done and i will modify the testcases
import { rest } from 'msw';
import { TEST_PAYLOAD, GET_EDITING_RESPONSE, START_EDITING_RESPONSE } from './fixtures';

export const magicShopifyOrderEditingHandler = [
  rest.get('*/1cc/magic/platform/orders/search', (req, res, ctx) =>
    res(ctx.status(200), ctx.json(TEST_PAYLOAD)),
  ),
  rest.get('*/1cc/magic/platform/order', (req, res, ctx) =>
    res(ctx.status(200), ctx.json(GET_EDITING_RESPONSE)),
  ),
  rest.post('*/1cc/magic/platform/order/edit/start', (req, res, ctx) =>
    res(ctx.status(200), ctx.json(START_EDITING_RESPONSE)),
  ),
];
