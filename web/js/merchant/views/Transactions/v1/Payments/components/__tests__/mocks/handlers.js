import { rest } from 'msw';

export const paymentHandlers = [
  rest.put('*/merchant/api/test/account/config', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/orders/:orderId/product_details', (req, res, ctx) => {
    const { orderId } = req.params;
    if (orderId === '123') {
      return res(
        ctx.json({
          data: {},
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.json({
        data: {
          payment_page: {
            title: 'Payment page title',
            id: 'Payment page ID',
          },
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/payment_pages/:paymentId/receipt', (req, res, ctx) => {
    const { paymentId } = req.params;
    if (paymentId === '123') {
      return res(
        ctx.json({
          data: {
            invoice_id: '123456',
            receipt_download_url: 'https://www.razorpay.com/receipt',
          },
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.json({
        data: {
          receipt: 'IN1234567890',
          invoice_id: '12345',
          receipt_download_url: 'https://www.razorpay.com/receipt',
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/payment_pages/:paymentId/send_receipt', (req, res, ctx) => {
    const { paymentId } = req.params;
    if (paymentId === '1234') {
      return res(
        ctx.errors([
          {
            message: 'Not authenticated',
            errorType: 'AuthenticationError',
          },
        ]),
        ctx.delay(50),
      );
    }

    return res(
      ctx.json({
        data: {
          success: true,
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/payment_pages/:paymentId/save_receipt', (req, res, ctx) => {
    const { paymentId } = req.params;
    const { receipt } = req.body;
    if (paymentId === '123' && receipt === 'IN1234567891') {
      return res(
        ctx.errors([
          {
            message: 'Not authenticated',
            errorType: 'AuthenticationError',
          },
        ]),
        ctx.delay(50),
      );
    }

    return res(
      ctx.json({
        success: true,
        data: {
          receipt: 'IN1234567890',
          receipt_download_url: 'https://www.razorpay.com/receipt',
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/orders/:orderId/line_items', (req, res, ctx) => {
    const { orderId } = req.params;
    if (orderId === '123') {
      return res(
        ctx.json({
          data: null,
        }),
        ctx.delay(50),
      );
    }
    return res(
      ctx.json({
        data: {
          items: [
            {
              name: 'item name 1',
              amount: 931860,
              currency: 'INR',
              net_amount: 944860,
              quantity: 10,
            },
            {
              name: 'item name 2',
              amount: 90,
              currency: 'INR',
              net_amount: 98,
            },
          ],
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/payments', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 200,
        data: {
          items: [],
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.get('*/payments/:paymentId', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 200,
        data: {
          id: 'pay_1234',
          status: 'authorized',
          method: 'bank_transfer',
          notes: {},
          transaction: {
            settlement: {
              id: 'setl_1234',
            },
          },
        },
      }),
      ctx.delay(50),
    );
  }),
  rest.post('*/payments/:paymentId/capture', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 200,
        data: {
          id: 'pay_1234',
          status: 'authorized',
          method: 'bank_transfer',
        },
      }),
      ctx.delay(50),
    );
  }),
];
