import { getMandatoryHeaders, getPhpBaseUrl } from '../../utils';
import { ShellError } from '../../utils/error-utils';
import { shellFetch, Response, Request } from '../shellFetch';

export const switchCurrentMerchant = async (req: Request, res: Response, mid: string) => {
  const dashboardBackendBaseUrl = getPhpBaseUrl(req);

  return await shellFetch(`${dashboardBackendBaseUrl}/settings/merchants/switch/${mid}`, {
    method: 'GET',
    headers: getMandatoryHeaders(req) as unknown as HeadersInit,
  })
    .then((response) => {
      req.shellLogger.info({
        message: `Switch current fetch status: ${response.status}`,
        moduleName: '@switchCurrentMerchant',
      });
      const { status } = response;
      if (status === 200) {
        return response.json();
      } else {
        throw new ShellError({
          moduleName: '@switchCurrentMerchant',
          message: `Switch current fetch failed.`,
          statusCode: status,
        });
      }
    })
    .then((response) => {
      const { success } = response as any;
      if (success) {
        req.shellLogger.success({
          message: 'Switch current success.',
          moduleName: '@switchCurrentMerchant',
        });

        return;
      } else {
        throw new ShellError({
          moduleName: '@switchCurrentMerchant',
          message: `Invalid API Response`,
        });
      }
    });
};
