import { rest } from 'msw';

export const paymentHandleHandlers = [
  rest.post('*/merchant/api/:mode/payment_handle/custom_amount', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          encrypted_amount: 'xzFti%2BvywBCUE1xQva30KQ%3D%3D',
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/merchant/api/:mode/payment_handle', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          title: 'Pizzapalooza',
          slug: '@pizzapalooza6839',
          url: 'https://rzpme.np.razorpay.in/@pizzapalooza6839',
          id: 'pl_K4NtgUwjR6jXEn',
        },
      }),
      ctx.delay(50),
    );
  }),
];
