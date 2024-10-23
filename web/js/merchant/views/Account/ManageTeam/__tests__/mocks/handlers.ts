import { rest } from 'msw';

export function fetchUserPassword() {
  return rest.get('*/merchant/api/*/users/set/password', (_, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({ status_code: 200, success: true, data: { set_password: true } }),
    );
  });
}

export function fetch2FaStatus() {
  return rest.post('*/merchant/api/*/users/2fa', (_, res, ctx) => {
    return res(ctx.status(200), ctx.json({ status_code: 200, success: true, data: [] }));
  });
}

export function verifyOtp() {
  return rest.post('*/user/otp/verify', (_, res, ctx) => {
    return res(ctx.status(200), ctx.json({ success: true, data: [] }));
  });
}
