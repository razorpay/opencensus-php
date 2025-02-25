import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type RedirectionMiddleware = () => ChildMiddlewareType;

export const redirectionMiddleware: RedirectionMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: 'Redirect check started...',
      moduleName: '@redirectionMiddleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(`${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.SHELL_REDIRECT}`, {
      method: 'GET',
      headers: getMandatoryHeaders(req) as unknown as HeadersInit,
    })
      .then((response) => {
        const setCookiesForRedirection = response.headers.raw()['set-cookie'];
        dashboardBackendRequestId = response.headers.get('x-request-id');

        req.shellLogger.info({
          message: `Redirect check status: ${response.status}`,
          moduleName: '@redirectionMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });

        if (setCookiesForRedirection) {
          res.locals['x-redirection-set-cookie'] = setCookiesForRedirection;
          req.shellLogger.info({
            message: `Set-Cookie headers retrieved and stored in locals.`,
            moduleName: '@redirectionMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@redirectionMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@redirectionMiddleware',
            message: `Redirection Check Failed.`,
            statusCode: status,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then((response) => {
        const { destination, destination_url } = response || ({} as any);
        if (destination) {
          res.locals.destination = destination;
          res.locals.destination_url = destination_url;
          req.shellLogger.success({
            message: 'Redirection checked.',
            moduleName: '@redirectionMiddleware',
            context: {
              destination: res.locals.destination,
              destination_url: res.locals.destination_url,
              dashboardBackendRequestId,
            },
          });
        } else {
          throw new ShellError({
            moduleName: '@redirectionMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      });
  };
