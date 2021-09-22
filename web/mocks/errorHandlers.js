import { rest } from 'msw';

const returnInternalServerError = (req, res, ctx) =>
  res(ctx.status(500), ctx.json({ status: 500, responseJSON: 'Internal server error' }));

export const errorHandlers = {
  internalServerError: rest.get('*/', (req, res, ctx) => returnInternalServerError(req, res, ctx)),
};
