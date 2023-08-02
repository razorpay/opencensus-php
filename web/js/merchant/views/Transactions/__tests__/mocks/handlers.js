import { rest } from 'msw';
import { merchantTokenPageDetails } from 'merchant/views/Transactions/__tests__/mocks/fixtures/BatchPayments/BatchUpload';

export const getMerchantTokenSuccess = () => {
  return rest.post('*/merchant/token', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: merchantTokenPageDetails,
      }),
      ctx.delay(50),
    );
  });
};
