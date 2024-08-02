import { rest } from 'msw';
import { updateNameSuccessResponse } from './fixtures';

export const updateNameSuccess = () => {
  return rest.post('*/users/update_name', (_req, res, ctx) => {
    return res(ctx.status(200), ctx.json(updateNameSuccessResponse), ctx.delay(50));
  });
};
