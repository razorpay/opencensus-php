import { getDialCodeByCountryCode } from '@razorpay/i18nify-js/phoneNumber';
import errorService from '@razorpay/universe-utils/errorService';

import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { getUser } from 'merchant/store';
import { merchantFetch } from 'merchant/utils/ajax';
import { createPaymentLinkV2 } from 'merchant/views/PaymentLinks/PaymentLinks/model';

export const fetchEligibilityApi = async (orderAmount: number, mobileNumber: string) => {
  const user = getUser();

  try {
    const response = await merchantFetch({
      url: 'merchant/customers/eligibility',
      method: 'post',
      mode: process.env.PUBLIC_ENV !== 'production' ? 'test' : 'live',
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        amount: orderAmount,
        currency: user?.merchant?.currency,
        customer: {
          contact: `${getDialCodeByCountryCode(user?.merchant?.country_code)}${mobileNumber}`,
        },
      },
    });

    if (response.data) {
      return response.data;
    }
    return response;
  } catch (error) {
    errorService.captureError(error, {
      tags: {
        team: Teams.AFFORDABILITY,
      },
      rank: Ranks.P2,
    });

    return error;
  }
};

export const sendPaymentLinkRequest = async (reqData, paymentLinkData) => {
  const nowUTC = Date.now();

  // Add 24 hours to the current Unix timestamp in milliseconds
  const timestampAfter24Hours = nowUTC + 24 * 60 * 60 * 1000;

  const response = await createPaymentLinkV2({
    ...reqData,
    expire_by: timestampAfter24Hours,
    options: {
      checkout: {
        config: {
          display: {
            blocks: {
              banks: {
                name: paymentLinkData?.name,
                instruments: [
                  {
                    method: paymentLinkData?.method,
                    [paymentLinkData?.method === 'emi' ? 'issuers' : 'providers']: [
                      paymentLinkData?.provider,
                    ],
                  },
                ],
              },
            },
            sequence: ['block.banks'],
            preferences: {
              show_default_blocks: false,
            },
          },
        },
      },
    },
  });

  return response;
};
