import { rest } from 'msw';

export const handlers = [];

export const errorHandlers = {
  internalServerError: rest.get('*/', (req, res, ctx) =>
    res(ctx.status(500), ctx.json({ status: 500, responseJSON: 'Internal server error' })),
  ),
};
