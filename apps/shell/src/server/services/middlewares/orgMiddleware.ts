import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type OrgMiddleware = () => ChildMiddlewareType;

export const orgMiddleware: OrgMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: 'Org data fetch started...',
      moduleName: '@orgMiddleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(`${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.ORG}`, {
      method: 'GET',
      headers: getMandatoryHeaders(req) as unknown as HeadersInit,
    })
      .then((response) => {
        dashboardBackendRequestId = response.headers.get('x-request-id');

        req.shellLogger.info({
          message: `Org data fetch status: ${response.status}`,
          moduleName: '@orgMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        const { status } = response;
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@orgMiddleware',
            message: `Org data fetch failed.`,
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
          res.locals.org = data;

          req.shellLogger.success({
            message: 'Org data fetched.',
            moduleName: '@orgMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });

          return;
        } else {
          throw new ShellError({
            moduleName: '@orgMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      });
  };
