import { rest } from 'msw';

export const fetchEligibilityHandler = () =>
  rest.get('*/merchant/activation/clarifications/eligibility', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          nc_revamp_enabled: true,
        },
      }),
      ctx.delay(50),
    );
  });
