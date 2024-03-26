import { rest } from 'msw';
import { navigationApi } from './fixtures/Sidebar';

export const sideBarHandlers = [
  rest.get('*/merchant/navigation', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: navigationApi,
      }),
      ctx.delay(50),
    );
  }),
];
