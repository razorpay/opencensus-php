import { graphql, HttpResponse, http } from 'msw';
import {
  MerchantByIdMock,
  merchantModularOnboardingDetailsAsSalesMock,
  salesOnboardedMerchantsMock,
  incompleteSalesOnboardingDetailsMock,
  MerchantByIdStatusMock,
  emptySalesOnboardedMerchantsMock,
  incompleteSalesOnboardingDetailsDirectModelMock,
  MerchantVerifyMobileOtpResponse,
} from './fixtures';
import {
  completedAdditionalDetailsAggregatorModelMock,
  completedAdditionalDetailsDirectModelMock,
} from './additionalDetails';
import { MERCHANT_API_LIVE_ENDPOINT } from 'apps/pos/e2e/constants';

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
  SendOTP: http.post('/user/register/otp', () => {
    return HttpResponse.json({
      status_code: 200,
      success: true,
      data: { token: 'PpvdeyPZxzh4fp' },
    });
  }),
  VerifyOTP: http.post(`${MERCHANT_API_LIVE_ENDPOINT}/register/merchant/otp/verify`, () => {
    return HttpResponse.json(MerchantVerifyMobileOtpResponse);
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
