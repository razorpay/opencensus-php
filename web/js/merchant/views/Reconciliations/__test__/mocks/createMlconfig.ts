import { rest } from 'msw';

const createMlConfig = () =>
  rest.post('*/recon-saas/reporting/config', async (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          session_id: 'PfPJ5mqzBf2cg9',
          audit_log_id: 'PpEntOYscb03tR',
        },
      }),
      ctx.delay(50),
    );
  });

export { createMlConfig };
