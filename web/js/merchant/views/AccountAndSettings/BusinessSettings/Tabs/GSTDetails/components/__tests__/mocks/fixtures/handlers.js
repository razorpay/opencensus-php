import { rest } from 'msw';

export const mockGSTSubmit = () => {
  return rest.post('*/merchant/api/live/merchant/gstin_self_serve', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: {
          gstin: '29AAGCR4375J1E4',
          sync_flow: true,
        },
      }),
    );
  });
};
