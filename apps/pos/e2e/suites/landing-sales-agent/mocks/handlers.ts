import { graphql, HttpResponse } from 'msw';
import {
  MerchantByIdMock,
  merchantModularOnboardingDetailsAsSalesMock,
  salesOnboardedMerchantsMock,
  incompleteSalesOnboardingDetailsMock,
  MerchantByIdStatusMock,
  emptySalesOnboardedMerchantsMock,
  incompleteSalesOnboardingDetailsDirectModelMock,
} from './fixtures';
import {
  completedAdditionalDetailsAggregatorModelMock,
  completedAdditionalDetailsDirectModelMock,
} from './additionalDetails';

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
  EmptySalesOnboardedMerchantsMock: graphql.query('SalesOnboardedMerchants', () => {
    return HttpResponse.json({
      data: {
        salesOnboardedMerchants: emptySalesOnboardedMerchantsMock,
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
  IncompleteMerchantModularOnboardingDetailsAsSales: graphql.query(
    'MerchantModularOnboardingDetailsAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsAsSales: incompleteSalesOnboardingDetailsMock,
        },
      });
    },
  ),
  IncompleteSalesOnboardingDetailsDirectModelMock: graphql.query(
    'MerchantModularOnboardingDetailsAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsAsSales: incompleteSalesOnboardingDetailsDirectModelMock,
        },
      });
    },
  ),
  completedAdditionalDetailsAggregatorModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales:
            completedAdditionalDetailsAggregatorModelMock,
        },
      });
    },
  ),
  completedAdditionalDetailsDirectModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: completedAdditionalDetailsDirectModelMock,
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
  MerchantByIdStatus: graphql.query('MerchantById', () => {
    return HttpResponse.json({
      data: {
        merchantById: MerchantByIdStatusMock,
      },
    });
  }),
};

export const landingPageMocks = [...Object.values(queryMocks)];
