import { graphql, HttpResponse } from 'msw';
import { salesOnboardedMerchantsMock } from './fixtures';

/**
 * Module level handlers
 */
export const queryMocks = {
  SalesOnboardedMerchants: graphql.query('SalesOnboardedMerchants', () => {
    console.log('GraphQL request intercepted: SalesOnboardedMerchants');
    return HttpResponse.json({
      data: {
        salesOnboardedMerchants: salesOnboardedMerchantsMock,
      },
    });
  }),
};

export const landingPageMocks = [...Object.values(queryMocks)];
