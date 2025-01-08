import { rest } from 'msw';

export const exportLinkHandlers = {
  success: () =>
    rest.get('/merchant/api/live/payments_cross_border_live/v1/export-link', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            export_id: 'export_id',
          },
        }),
      );
    }),
  createExportLink: () =>
    rest.post('/merchant/api/live/payments_cross_border_live/v1/export-link', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            export_id: 'export_id',
          },
        }),
      );
    }),
};
