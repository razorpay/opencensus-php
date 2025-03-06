import { rest } from 'msw';
import { EventConfigResponse, CampaignWalletsResponse, CampaignListResponse } from './fixtures';

export default [
  rest.get('*/engage/event_configs', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(EventConfigResponse), ctx.delay(1));
  }),

  rest.post('*/wallet/proxy/issuing/campaign_program', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: '123',
          name: 'Campaign1',
          type: 'trigger',
          merchant_id: 'merchant1',
        },
      }),
      ctx.delay(1),
    );
  }),

  rest.post('*/engage/campaigns/onboard', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json(
        req?.body?.['campaign_name'] === 'Failure Campaign'
          ? {
              status_code: 500,
              success: false,
              errors: [
                "Dear merchant, We're currently fixing an unexpected issue. Sorry for any inconvenience!",
              ],
            }
          : {
              status_code: 200,
              success: true,
              data: {
                message: 'Campaign onboarded successfully',
              },
            },
      ),
      ctx.delay(1),
    );
  }),

  rest.get('*/engage/campaigns', (req, res, ctx) => {
    if (req.url.searchParams.get('page_no') === '1') {
      return res(ctx.status(200), ctx.json(CampaignListResponse[0]), ctx.delay(1));
    } else if (req.url.searchParams.get('page_no') === '2') {
      return res(ctx.status(200), ctx.json(CampaignListResponse[1]), ctx.delay(1));
    }
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          campaigns: [
            {
              id: 'PupTd4jjuih5AX',
              name: 'Spring campaign',
              merchant_id: 'NDnRD3epJ6P60L',
              event_config_id: 'PL1xe9evfew3Ve',
              status: 'ACTIVE',
              starts_at: 1739397600,
              ends_at: 1740691800,
              has_usage_limits: true,
              created_at: 1739369894,
              updated_at: 1739369894,
            },
            {
              id: 'PuRanmWywv4IiE',
              name: 'Testing FE campaign',
              merchant_id: 'NDnRD3epJ6P60L',
              event_config_id: 'EI1xe9evfew3Vp',
              status: 'ACTIVE',
              starts_at: 1739285782,
              has_usage_limits: true,
              created_at: 1739285783,
              updated_at: 1739285783,
            },
          ],
          pagination: { page_no: 1, page_size: 10, total_pages: 2, next_page_no: 2 },
        },
      }),
      ctx.delay(1),
    );
  }),
];
