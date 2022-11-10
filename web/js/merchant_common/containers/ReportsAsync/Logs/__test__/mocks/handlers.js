import { rest } from 'msw';

const logHandlers = [
  rest.get('*/merchant/api/test/ufh/file/:fileId/get-signed-url', (req, res, ctx) => {
    const { fileId } = req.params;
    if (fileId === 'error-id' || fileId === 'no-error-response') {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: false,
          errors: fileId === 'error-id' ? ['file cannot be downloaded'] : undefined,
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: { signed_url: 'test-signed-url' },
      }),
    );
  }),
];

export default logHandlers;
