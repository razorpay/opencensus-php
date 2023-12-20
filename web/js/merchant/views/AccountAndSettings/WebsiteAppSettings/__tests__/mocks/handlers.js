import { rest } from 'msw';
import {
  requestedConnectedAppsResponse,
  requestMerchantWebsiteDetailsRes,
} from './fixtures/WebsiteAndAppSettings';

export const fetchAddWebsiteWorkflowStatusHandler = () =>
  rest.get('*/merchant/api/:mode/merchant/activation/websites/status', (req, res, ctx) => {
    return res.once(
      ctx.json({
        status_code: 200,
        success: true,
        data: false,
      }),
    );
  });

export const fetchWebsiteAutomationStatus = (mid) => {
  return rest.get(
    `*/merchant/api/:mode/feature/merchant/${mid}/website_automated_checks`,
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            status: false,
          },
        }),
      );
    },
  );
};

export const fetchWorkflowStatus = (workflowType) => {
  return rest.get(`*/merchant/api/:mode/merchant/${workflowType}/details`, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          workflow_exists: true,
          workflow_status: 'open',
          needs_clarification: 'Website not correct',
          permission: 'update_merchant_website',
          request_under_validation: false,
          tags: ['awaiting-customer-response'],
        },
      }),
    );
  });
};

export const fetchMerchantWebsiteDetailsHandler = () =>
  rest.get('*/merchant/api/:mode/merchant/website/section', (req, res, ctx) => {
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
