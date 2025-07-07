import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit, Response } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantPayoutsFetchMiddleware = () => ChildMiddlewareType;

/**
 * Fetches Merchant Payouts data from both live and test endpoints concurrently.
 */
export const merchantPayoutsFetchMiddleware: MerchantPayoutsFetchMiddleware =
  () =>
  (req, res): Promise<void> => {
    req.shellLogger.info({
      message: 'Merchant Payouts data fetch started...',
      moduleName: '@merchantPayoutsFetchMiddleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    const fetchOptions = {
      method: 'GET',
      headers: getMandatoryHeaders(req) as HeadersInit,
    };

    const liveUrl = `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_PAYOUTS_LIVE}`;
    const testUrl = `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_PAYOUTS_TEST}`;

    const processResponse = (response: Response, endpoint: 'live' | 'test'): Promise<any> => {
      const dashboardBackendRequestId = response.headers.get('x-request-id') || undefined;

      if (endpoint === 'live') {
        const setCookies = response.headers.raw()['set-cookie'];

        if (setCookies) {
          res.locals['x-set-cookie'] = setCookies;
          // Store cookies for forwarding to subsequent middleware and API calls
          req.forward_cookies = setCookies;

          req.shellLogger.info({
            message: 'Set-Cookie headers retrieved and stored in locals.',
            moduleName: '@merchantPayoutsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: 'No Set-Cookie headers found.',
            moduleName: '@merchantPayoutsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      }

      if (response.status !== 200) {
        req.shellLogger.warn({
          message: `Merchant Payouts ${endpoint} data fetch failed with status: ${response.status}`,
          moduleName: '@merchantPayoutsFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        throw new ShellError({
          moduleName: '@merchantPayoutsFetchMiddleware',
          message: `Merchant Payouts ${endpoint} data fetch failed.`,
          statusCode: response.status,
          context: {
            dashboardBackendRequestId,
          },
        });
      }

      return response.json().then((json: any) => {
        if (!json?.success) {
          throw new ShellError({
            moduleName: '@merchantPayoutsFetchMiddleware',
            message: `Invalid API Response for Merchant Payouts ${endpoint}`,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
        req.shellLogger.success({
          message: `Merchant Payouts ${endpoint} data fetched.`,
          moduleName: '@merchantPayoutsFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        return json?.data;
      });
    };

    return Promise.all([shellFetch(liveUrl, fetchOptions), shellFetch(testUrl, fetchOptions)])
      .then(([liveResponse, testResponse]) => {
        return Promise.all([
          processResponse(liveResponse, 'live'),
          processResponse(testResponse, 'test'),
        ]);
      })
      .then(([liveData, testData]) => {
        res.locals.merchant_payouts_live = liveData;
        res.locals.merchant_payouts_test = testData;
      })
      .catch((error: any) => {
        req.shellLogger.warn({
          message: `Error in Merchant Payouts fetch middleware: ${error.message}`,
          moduleName: '@merchantPayoutsFetchMiddleware',
        });
        throw error;
      });
  };
