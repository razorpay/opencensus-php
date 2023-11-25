import { rest } from 'msw';

export const mockFetchGST = () => {
  return rest.get('*/merchant/api/live/merchant/gst', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: {
          gstin: '29AAGCR4375J1E4',
          p_gstin: null,
        },
      }),
    );
  });
};

export const mockFetchGSTList = () => {
  return rest.get('*/merchant/api/:mode/merchant/activation/gst_details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: {
          results: ['29AAGCR4375J1E4', '29AAGCR4375J1W3'],
        },
      }),
    );
  });
};
