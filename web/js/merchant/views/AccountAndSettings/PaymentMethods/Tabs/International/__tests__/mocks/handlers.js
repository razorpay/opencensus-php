import { rest } from 'msw';
import { ProductWorkflowStatesInBackend } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';

export const getProductStatusHandler = () =>
  rest.get(
    '/merchant/api/:mode/merchants/product_international/workflow/status/all?version=v2',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            data: {
              payment_gateway: ProductWorkflowStatesInBackend.APPROVED,
              payment_links: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
              payment_pages: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
              invoices: ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
            },
          },
        }),
        ctx.delay(50),
      );
    },
  );

export const fetchInternationalWorkflowStatus = (response) => {
  return rest.get(
    `/merchant/api/:mode/merchant/${WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI}/details`,
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json(
          response
            ? response
            : {
                status_code: 200,
                success: true,
                data: {
                  workflow_exists: false,
                  needs_clarification: null,
                  permission: null,
                  request_under_validation: false,
                  tags: [],
                },
              },
        ),
        ctx.delay(50),
      );
    },
  );
};

export const fetchB2BFeatureStatus = (status = true) => {
  return rest.get(
    '/merchant/api/:mode/feature/:userid/enable_intl_bank_transfer',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            status,
          },
        }),
        ctx.delay(50),
      );
    },
  );
};

export const fetchUser = () =>
  rest.get('/user', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          current: 'test',
          data: {
            features: [],
          },
          international: true,
        },
      }),
      ctx.delay(50),
    );
  });

export const fetchUserFeatures = () =>
  rest.get('/merchant/api/test/merchants/me/features', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          features: [],
        },
      }),
      ctx.delay(50),
    );
  });
