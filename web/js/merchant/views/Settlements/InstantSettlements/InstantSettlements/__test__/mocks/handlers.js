import { rest } from 'msw';

export const instantsettlementsHandlers = () =>
  rest.get('*/settlements/ondemand', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          items: [
            {
              id: 'setlodp_InXEtJ23TyveQt',
              amount: 10000,
              amount_settled: 9971,
              amount_requested: 10000,
              fees: 29,
              scheduled: false,
              utr: null,
              status: 'initiated',
              created_at: 1643017682,
            },
            {
              id: 'setlodp_InXEtJ23Wqsse3',
              amount: 10000,
              amount_settled: 9971,
              fees: 29,
              scheduled: true,
              utr: 'qlirejc',
              amount_requested: 10000,
              status: 'initiated',
              created_at: 1643017682,
            },
          ],
        },
      }),
    );
  });

export const getOdsValidateHandler = (data) => {
  return rest.get('*/settlements/ondemand/feature/validate', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });
};

export const getOdsConfigHandler = (data, status = true) => {
  return rest.get('*/settlements/ondemand/merchant/config', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: status ? 200 : 500,
        success: !!status,
        data,
      }),
    );
  });
};

export const getPgBalanceHandler = (data = { balance: 1200040 }) => {
  return rest.get('*/balance', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });
};

export const getPricingBreakupHandler = (
  data = {
    items: [
      {
        name: 'settlement_ondemand',
        amount: 10,
        pricing_rule: {
          percent_rate: 10,
        },
      },
      {
        name: 'tax',
        amount: 2344,
        percentage: 8,
      },
    ],
  },
) => {
  return rest.get('*/settlement/ondemand/fees/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data,
      }),
    );
  });
};

export const getLinkedAccountBalanceHandler = (
  data = {
    balance: '4569000',
  },
) => {
  return rest.get(
    '*/capital_es/service/early_settlements/ondemand/route_settlement_balance',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data,
        }),
      );
    },
  );
};

export const getPostODSHandler = () => {
  return rest.post('*/settlement/ondemand/dashboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {},
      }),
    );
  });
};

export const getPostRouteODSHandler = () => {
  return rest.post(
    '*/capital_es/service/early_settlements/ondemand/route_settlements',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {},
        }),
      );
    },
  );
};
