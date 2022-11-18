import { rest } from 'msw';
import {
  configs,
  logs,
  linkedAccounts,
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
];

export default reportsHandlers;
