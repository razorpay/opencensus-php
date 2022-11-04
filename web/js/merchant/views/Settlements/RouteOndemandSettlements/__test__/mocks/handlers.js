import { rest } from 'msw';

export const settlementErrorhandler = () =>
  rest.get('*/capital_es/service/early_settlements/ondemand_triggers', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: false,
        errors: 'Error in fetching ondemand settlements from server',
      }),
    );
  });

export const settlementSuccesshandler = ({ initialState }) =>
  rest.get('*/capital_es/service/early_settlements/ondemand_triggers', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        items: initialState.routeOndemandSettlements.items,
      }),
    );
  });
