import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { getErrorMessage, ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantFeaturesFetchMiddleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const merchantFeaturesFetchMiddleware: MerchantFeaturesFetchMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Merchant Features data fetch started...`,
      moduleName: '@merchantFeaturesFetchMiddleware',
    });

    if (!Boolean(res.locals.user_session?.merchant_id)) {
      req.shellLogger.warn({
        message: 'Fallback to empty merchant_features object',
        moduleName: '@merchantFeaturesFetchMiddleware',
      });
      res.locals.merchant_features = [];
      return;
    }

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(
      `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_FEATURES}`,
      {
        method: 'GET',
        headers: getMandatoryHeaders(req) as unknown as HeadersInit,
      },
    )
      .then((response) => {
        const setCookies = response.headers.raw()['set-cookie'];
        dashboardBackendRequestId = response.headers.get('x-request-id');

        if (setCookies) {
          res.locals['x-set-cookie'] = setCookies;
          req.shellLogger.info({
            message: `Set-Cookie headers retrieved and stored in locals.`,
            moduleName: '@merchantFeaturesFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@merchantFeaturesFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Merchant Features data fetch status: ${response.status}`,
          moduleName: '@merchantFeaturesFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@merchantFeaturesFetchMiddleware',
            message: `Merchant Features data fetch failed.`,
            statusCode: status,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then((response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.merchant_features = data;
          req.shellLogger.success({
            message: `Merchant Features data fetched.`,
            moduleName: '@merchantFeaturesFetchMiddleware',
            context: {
              merchant_features_length: res?.locals?.merchant_features?.length,
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@merchantFeaturesFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
              response,
            },
          });
        }
      });
  };
