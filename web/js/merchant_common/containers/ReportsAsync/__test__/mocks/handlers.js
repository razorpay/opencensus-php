import { rest } from 'msw';
import {
  configs,
  logs,
  linkedAccounts,
  configComponents,
  customReport,
  configDetails,
} from 'merchant_common/containers/ReportsAsync/__test__/mocks/fixtures/data';

const reportsHandlers = [
  // Fetch Reporting configs
  rest.get('*/merchant/api/*/reporting/configs', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: configs.length,
          items: configs,
          entity: 'collection',
        },
      }),
      ctx.delay(50),
    );
  }),

  // Fetch logs
  rest.get('*/merchant/api/*/reporting/logs', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: logs.length,
          items: logs,
          entity: 'collection',
        },
      }),
      ctx.delay(50),
    );
  }),

  // Fetch linked accounts
  rest.get('*/merchant/api/*/linked_accounts', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: linkedAccounts.length,
          items: linkedAccounts,
          entity: 'collection',
        },
      }),
      ctx.delay(50),
    );
  }),

  // Fetch config components
  rest.get('*/merchant/api/*/reporting/merchant/config-components/orders', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          fields: configComponents.fields,
          filters: configComponents.filters,
          scopes: [],
        },
      }),
    );
  }),

  //Create custom report
  rest.post('*/merchant/api/*/reporting/merchant/configs', (_req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: customReport,
      }),
    );
  }),

  // Fetch config details by Id
  rest.get('*/reporting/configs/config_LJnCUBLc2Yinse', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: configDetails,
      }),
    );
  }),
];

export default reportsHandlers;
