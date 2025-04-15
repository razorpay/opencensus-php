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
  pendingStateAgreement,
  agreementOnlineCompleteStateMock,
  agreementOnlineMutionStateMock,
  salesActivatedMerchantsMock,
  PastSevenDaysMerchantsMock,
  agreementOfflineCompleteStateMock,
} from './fixtures';
import {
  completedAdditionalDetailsAggregatorModelMock,
  completedAdditionalDetailsDirectModelMock,
} from './additionalDetails';
import {
  nextStepNACHDirectModelMock,
  NACHFormSubmissionDirectModelMock,
  brandInformationAggregatorModelMock,
  brandInformationValidationAggregatorModelMock,
  nextStepNACHAggregatorModelMock,
} from './paymentMethodAndService';
import {
  addToCartMock,
  proceedToCartMock,
  confirmOrderMock,
  confirmDeliveryAddressMock,
  confirmedPaymentMock,
  paymentPendingMock,
} from './deviceSelection';
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
  ActivatedSalesOnboardedMerchants: graphql.query('SalesOnboardedMerchants', () => {
    return HttpResponse.json({
      data: {
        salesOnboardedMerchants: salesActivatedMerchantsMock,
      },
    });
  }),
  PastSevenDaysMerchants: graphql.query('SalesOnboardedMerchants', () => {
    return HttpResponse.json({
      data: {
        salesOnboardedMerchants: PastSevenDaysMerchantsMock,
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
  PricingStepDirectModelSelectionMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsAsSales: incompleteSalesOnboardingDetailsDirectModelMock,
        },
      });
    },
  ),
  PricingStepAggregatorModelSelectionMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsAsSales: incompleteSalesOnboardingDetailsMock,
        },
      });
    },
  ),
  CustomRatesProofFileUploadMock: http.post(
    `${MERCHANT_API_LIVE_ENDPOINT}/merchant/documents/upload`,
    () => {
      return HttpResponse.json({
        status_code: 200,
        success: true,
        data: {
          custom_pricing_proof: {
            file_id: 'PraczZLgWDASGv',
            source: 'UFH',
            file_name: 'api/PmTPcHMAUczy56/3f493/test-document',
            original_file_name: 'test-document.png',
          },
        },
      });
    },
  ),
  NachFileUploadMock: http.post(`${MERCHANT_API_LIVE_ENDPOINT}/merchant/documents/upload`, () => {
    return HttpResponse.json({
      status_code: 200,
      success: true,
      data: {
        nach: {
          file_id: 'Prdcm0g2rDxGdP',
          source: 'UFH',
          file_name: 'api/PmTPcHMAUczy56/77c74/custom-rates-proof',
          original_file_name: 'test-document.png',
        },
      },
    });
  }),
  NachFileUploadFailure: http.post(
    `${MERCHANT_API_LIVE_ENDPOINT}/merchant/documents/upload`,
    () => {
      return HttpResponse.json({
        status_code: 500,
        success: false,
      });
    },
  ),
  BrandInformationAggregatorModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: brandInformationAggregatorModelMock,
        },
      });
    },
  ),
  BrandInformationValidationAggregatorModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales:
            brandInformationValidationAggregatorModelMock,
        },
      });
    },
  ),
  NextStepNACHAggregatorModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: nextStepNACHAggregatorModelMock,
        },
      });
    },
  ),
  NextStepNachDirectModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: nextStepNACHDirectModelMock,
        },
      });
    },
  ),
  NACHFormSubmissionDirectModelMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: NACHFormSubmissionDirectModelMock,
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
  AddToCartMock: graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsUpdateAsSales: addToCartMock,
      },
    });
  }),
  ProceedToCartMock: graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsUpdateAsSales: proceedToCartMock,
      },
    });
  }),
  CustomDeviceChargesProof: http.post(
    `${MERCHANT_API_LIVE_ENDPOINT}/merchant/documents/upload`,
    () => {
      return HttpResponse.json({
        status_code: 200,
        success: true,
        data: {
          custom_device_charges_proof: {
            file_id: 'PsouitDtdwtMFQ',
            source: 'UFH',
            file_name: 'api/PmTPcHMAUczy56/3505b/test-document',
            original_file_name: 'test-document.png',
          },
        },
      });
    },
  ),
  ConfirmOrderMock: graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsUpdateAsSales: confirmOrderMock,
      },
    });
  }),
  ConfirmDeliveryAddressMock: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: confirmDeliveryAddressMock,
        },
      });
    },
  ),
  ConfirmedPaymentMock: graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsUpdateAsSales: confirmedPaymentMock,
      },
    });
  }),
  PaymentPendingMock: graphql.mutation('MerchantModularOnboardingDetailsUpdateAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsUpdateAsSales: paymentPendingMock,
      },
    });
  }),
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

  // agreement signing mocks

  AgreementPendingState: graphql.query('MerchantModularOnboardingDetailsAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsAsSales: pendingStateAgreement,
      },
    });
  }),

  AgreementOnlineModeMutation: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: agreementOnlineMutionStateMock,
        },
      });
    },
  ),

  AgreementOnlineCompleted: graphql.query('MerchantModularOnboardingDetailsAsSales', () => {
    return HttpResponse.json({
      data: {
        merchantModularOnboardingDetailsAsSales: agreementOnlineCompleteStateMock,
      },
    });
  }),
  AgreementFileUploadMock: http.post(
    `${MERCHANT_API_LIVE_ENDPOINT}/merchant/documents/upload`,
    () => {
      return HttpResponse.json({
        status_code: 200,
        success: true,
        data: {
          merchant_agreement: {
            file_id: 'Prdcm0g2rDxGdP',
            source: 'UFH',
            file_name: 'api/PmTPcHMAUczy56/77c74/custom-rates-proof',
            original_file_name: 'test-document.png',
          },
        },
      });
    },
  ),
  AgreementFileUploadFailure: http.post(
    `${MERCHANT_API_LIVE_ENDPOINT}/merchant/documents/upload`,
    () => {
      return HttpResponse.json({
        status_code: 400,
        success: false,
        errors: ['The file must be a file of type: pdf, jpeg, jpg, png, jfif, heic, heif.'],
      });
    },
  ),
  AgreementOfflineCompleteState: graphql.mutation(
    'MerchantModularOnboardingDetailsUpdateAsSales',
    () => {
      return HttpResponse.json({
        data: {
          merchantModularOnboardingDetailsUpdateAsSales: agreementOfflineCompleteStateMock,
        },
      });
    },
  ),
};

export const landingPageMocks = [...Object.values(queryMocks)];
