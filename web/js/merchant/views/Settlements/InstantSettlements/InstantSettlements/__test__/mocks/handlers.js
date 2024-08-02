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
