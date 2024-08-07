import { graphql } from 'msw';
import { SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE } from './fixtures';

export const getSalesMappedMerchantsHandler = ({ type }: { type: string }): any => {
  if (type === 'success') {
    return graphql.query('SalesOnboardedMerchants', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.data(SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE), ctx.delay(0));
    });
  }

  if (type === 'empty') {
    return graphql.query('SalesOnboardedMerchants', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.data({
          salesOnboardedMerchants: { ...SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE, merchants: [] },
        }),
        ctx.delay(50),
      );
    });
  }
};
