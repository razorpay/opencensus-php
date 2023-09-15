import { rest } from 'msw';
export const fetchAnalyticsSettings = (payload) => {
  return rest.get('*/merchant/api/test/1cc/analytics/configs', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(payload), ctx.delay(50));
  });
};

export const saveEventConfigs = (payload) => {
  return rest.post('*/merchant/api/test/1cc/analytics/event_configs', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(payload), ctx.delay(50));
  });
};
