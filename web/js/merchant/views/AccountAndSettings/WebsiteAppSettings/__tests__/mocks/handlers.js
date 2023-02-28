import { rest } from 'msw';
import {
  requestedConnectedAppsResponse,
  requestMerchantWebsiteDetailsRes,
} from './fixtures/WebsiteAndAppSettings';

export const fetchAddWebsiteWorkflowStatusHandler = () =>
  rest.get('*/merchant/api/live/merchant/activation/websites/status', (req, res, ctx) => {
    return res.once(
      ctx.json({
        status_code: 200,
        success: true,
        data: false,
      }),
    );
  });

export const fetchMerchantWebsiteDetailsHandler = () =>
  rest.get('*/merchant/api/test/merchant/website/section', (req, res, ctx) => {
    return res.once(
      ctx.json({
        status_code: 200,
        success: true,
        data: requestMerchantWebsiteDetailsRes,
      }),
      ctx.delay(50),
    );
  });

export const fetchConnectedApplicationsHandler = () =>
  rest.get('*/oauth/tokens/', (req, res, ctx) => {
    return res.once(
      ctx.json({
        status_code: 200,
        success: true,
        data: requestedConnectedAppsResponse,
      }),
    );
  });
