import { rest } from 'msw';
import { getSubscriptionDataRes } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/response';

const fetchEnrollmentStatusHandler = ({
  exists = 'true',
  message = '',
  responseStatus = 200,
  apiLevelStatus = 200,
  gsLevelStatus = 200,
  delay = 5000,
}: {
  exists?: 'true' | 'false';
  message?: string;
  responseStatus?: number;
  apiLevelStatus?: number;
  gsLevelStatus?: number;
  delay?: number;
} = {}) =>
  rest.get('*/subscriptions/exists', (req, res, ctx) => {
    return res(
      ctx.status(responseStatus),
      ctx.json({
        status_code: apiLevelStatus,
        success: true,
        data: {
          status_code: gsLevelStatus,
          response: {
            exists,
            message,
          },
        },
      }),
      ctx.delay(delay),
    );
  });

const fetchSubscriptionsDataHandler = ({
  subscriptionStatus,
  paymentSubscriptionStatus,
  responseStatus = 200,
  apiLevelStatus = 200,
  gsLevelStatus = 200,
  delay = 5000,
}: {
  subscriptionStatus?: string;
  paymentSubscriptionStatus?: string;
  responseStatus?: number;
  apiLevelStatus?: number;
  gsLevelStatus?: number;
  delay?: number;
} = {}) =>
  rest.get('*/subscriptions', (req, res, ctx) => {
    return res(
      ctx.status(responseStatus),
      ctx.json({
        status_code: apiLevelStatus,
        success: true,
        data: {
          status_code: gsLevelStatus,
          response: {
            ...getSubscriptionDataRes({ subscriptionStatus, paymentSubscriptionStatus }),
          },
        },
      }),
      ctx.delay(delay),
    );
  });

export { fetchEnrollmentStatusHandler, fetchSubscriptionsDataHandler };
