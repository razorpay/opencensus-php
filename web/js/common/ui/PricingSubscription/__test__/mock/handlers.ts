import { rest } from 'msw';
import { templateId } from 'common/ui/PricingSubscription/__test__/mock/fixtures';
import { pricing_bundle } from 'common/ui/PricingSubscription/__test__/PricingParentComponentsMockData';

const fetchGSModalHandler = ({
  responseStatus = 200,
  apiLevelStatus = 200,
  gsLevelStatus = 200,
  delay = 5000,
  shouldReturnEmptyData = false,
}: {
  responseStatus?: number;
  apiLevelStatus?: number;
  gsLevelStatus?: number;
  delay?: number;
  shouldReturnEmptyData?: boolean;
} = {}) =>
  rest.get(`*/template/${templateId}`, (req, res, ctx) => {
    const response = shouldReturnEmptyData
      ? {}
      : {
          template: {
            id: templateId,
            channel_id: 'HTdu8cC7FJEIHC',
            asset: 'JSON_SCHEMA',
            name: 'Pricing bundle for dashboard',
            description: 'Pricing asset bundle for PG testing',
            status: 'DRAFT',
            created_by: 'aman.bharadwaj@razorpay.com',
            created_at: '2023-01-30T10:29:18Z',
            updated_at: '2023-02-09T09:59:19Z',
            dynamic_asset_name: 'pricing_bundle',
            data: {
              ...pricing_bundle,
            },
          },
        };

    return res(
      ctx.status(responseStatus),
      ctx.json({
        status_code: apiLevelStatus,
        success: true,
        data: {
          status_code: gsLevelStatus,
          response,
        },
      }),
      ctx.delay(delay),
    );
  });

export { fetchGSModalHandler };
