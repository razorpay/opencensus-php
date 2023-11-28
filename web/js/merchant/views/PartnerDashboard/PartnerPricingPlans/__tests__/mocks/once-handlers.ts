import { rest } from 'msw';

import { server } from 'common/services/test/test-utils';
import { defaultPartnerPricing } from 'merchant/views/PartnerDashboard/PartnerPricingPlans/__tests__/mocks/fixtures';

export const useDefaultPartnerPricingHandler = () => {
  server.use(
    rest.get('*/merchant/api/test/partner_config/default', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            default_plan_id_details: defaultPartnerPricing,
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};
