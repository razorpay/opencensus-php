import { rest } from 'msw';
import { emailOtpErrorResponse, userRegisterOtpErrorResponse } from './fixtures';

import { server } from '@libs/web-nexus/common/services/test/test-utils';

export const mockEmailOtpSendError = () =>
  server.use(
    rest.post('*/merchant/api/live/merchant/activation/otp/send', (req, res, ctx) => {
      return res.once(ctx.status(200), ctx.json(emailOtpErrorResponse), ctx.delay(50));
    }),
  );

export const mockUserRegisterOtpError = () =>
  server.use(
    rest.post('*/user/register/otp', (req, res, ctx) => {
      return res.once(ctx.status(200), ctx.json(userRegisterOtpErrorResponse), ctx.delay(50));
    }),
  );
