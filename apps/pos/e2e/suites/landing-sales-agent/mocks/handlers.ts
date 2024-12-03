import { graphql, HttpResponse } from 'msw';
import {
  MerchantByIdMock,
  merchantModularOnboardingDetailsAsSalesMock,
  salesOnboardedMerchantsMock,
} from './fixtures';

/**
 * Module level handlers
 */
export const queryMocks = {
  SalesOnboardedMerchants: graphql.query('SalesOnboardedMerchants', () => {
    return HttpResponse.json({
      data: {
        salesOnboardedMerchants: salesOnboardedMerchantsMock,
      },
    });
  }),
  MerchantModularOnboardingDetailsAsSales: graphql.query(
    'MerchantModularOnboardingDetailsAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsAsSales: merchantModularOnboardingDetailsAsSalesMock,
        },
      });
    },
  ),
  MerchantById: graphql.query('MerchantById', () => {
    return HttpResponse.json({
      data: {
        merchantById: MerchantByIdMock,
      },
    });
  }),
};

export const landingPageMocks = [...Object.values(queryMocks)];
