import { rest } from 'msw';

import { GCMSReportLogResponse } from './fixtures';

export default [
  rest.get(`*/logs`, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(GCMSReportLogResponse), ctx.delay(0));
  }),
];
