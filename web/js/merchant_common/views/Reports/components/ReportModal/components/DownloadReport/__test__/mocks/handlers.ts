import { rest } from 'msw';

export const BATCH_PAGE_ITEMS = [
  { id: 'test_id_1', title: 'test_title_1' },
  { id: 'test_id_2', title: 'test_title_2' },
];

export const BATCH_ID_OPTIONS = ['test_batch_id_1', 'test_batch_id_2'];

export function getPaymentPagesFileUploadPages() {
  return rest.get('*/payment_pages', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: BATCH_PAGE_ITEMS.length,
          entity: 'test_entity',
          has_more: false,
          items: BATCH_PAGE_ITEMS,
        },
      }),
      ctx.delay(50),
    );
  });
}

export function getBatchIds() {
  return rest.get('*/payment_pages/:batchPaymentPageId/batches', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: BATCH_ID_OPTIONS,
      }),
      ctx.delay(50),
    );
  });
}
