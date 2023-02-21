import { rest } from 'msw';

const TokenResp = {
  entity: 'collection',
  count: 1,
  items: [
    {
      id: 'token_L7zPW1go48yDUo',
      entity: 'token',
      token: 'FGXurOUEnbkIsw',
      bank: null,
      wallet: null,
      method: 'upi',
      recurring: false,
      recurring_details: {
        status: 'initiated',
        failure_reason: null,
      },
      auth_type: null,
      mrn: null,
      used_at: null,
      created_at: 1674557866,
      customer: {
        id: 'cust_Kzi5cbvSHzFXdQ',
        entity: 'customer',
        name: 'testing',
        email: 'testing@testing.com',
        contact: '1234567890',
        gstin: null,
        notes: [],
        created_at: 1672750147,
      },
      start_time: 1674557857,
      notes: [],
      error_description: null,
      internal_error_code: null,
      source: 'merchant',
      dcc_enabled: false,
    },
  ],
};

const tokenDetails = {
  id: 'token_L7zPW1go48yDUo',
  entity: 'token',
  token: 'FGXurOUEnbkIsw',
  bank: null,
  wallet: null,
  method: 'upi',
  recurring: false,
  recurring_details: { status: 'confirmed', failure_reason: null },
  auth_type: null,
  mrn: null,
  used_at: null,
  created_at: 1674557866,
  start_time: 1674557857,
  notes: [],
  error_description: null,
  internal_error_code: null,
  source: 'merchant',
  dcc_enabled: false,
  customer: {
    id: 'cust_Kzi5cbvSHzFXdQ',
    entity: 'customer',
    name: 'testing',
    email: 'testing@testing.com',
    contact: '1234567890',
    gstin: null,
    notes: [],
    created_at: 1672750147,
  },
};

const nachTokenDetails = {
  ...tokenDetails,
  method: 'nach',
  recurring_details: { status: 'confirmed', failure_reason: 'Some error' },
};

const cancelTokenErrorRes = {
  status_code: 400,
  success: false,
  errors: ['Authentication failed', 'Status Code: 400'],
};

export const fetchTokens = () => {
  const url = '*/merchant/api/test/subscription_registration/tokens';

  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: TokenResp,
      }),
      ctx.delay(50),
    );
  });
};

export const fetchTokenDetails = (isNach = false) => {
  const url = '*/merchant/api/*/subscription_registration/tokens/*';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: isNach ? nachTokenDetails : tokenDetails,
      }),
      ctx.delay(50),
    );
  });
};

export const cancelToken = () => {
  const url = '*/merchant/api/*/customers/*/tokens/*/cancel';
  return rest.put(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 2,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const cancelTokenError = () => {
  const url = '*/merchant/api/*/customers/*/tokens/*/cancel';
  return rest.put(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(cancelTokenErrorRes), ctx.delay(50));
  });
};

export const deleteToken = () => {
  const url = '*/merchant/api/*/subscription_registration/tokens/*';
  return rest.delete(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 2,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const deleteTokenError = () => {
  const url = '*/merchant/api/*/subscription_registration/tokens/*';
  return rest.delete(url, (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(cancelTokenErrorRes), ctx.delay(50));
  });
};
// export const sendEmail = () => {
//   const url = '*/merchant/api/*/subscription_registration/tokens/*/notify_by/email';
//   return rest.post(url, (req, res, ctx) => {
//     return res(ctx.status(200), ctx.json({ success: true }), ctx.delay(50));
//   });
// };

// export const sendSms = () => {
//   const url = '*/merchant/api/*/subscription_registration/tokens/*/notify_by/sms';
//   return rest.post(url, (req, res, ctx) => {
//     return res(ctx.status(200), ctx.json({ success: true }), ctx.delay(50));
//   });
// };

// export const sendEmailError = () => {
//   const url = '*/merchant/api/*/subscription_registration/tokens/*/notify_by/email';
//   return rest.post(url, (req, res, ctx) => {
//     return res(ctx.status(200), ctx.json({ success: false }), ctx.delay(50));
//   });
// };

// export const sendSmsError = () => {
//   const url = '*/merchant/api/*/subscription_registration/tokens/*/notify_by/sms';
//   return rest.post(url, (req, res, ctx) => {
//     return res(ctx.status(200), ctx.json({ success: false }), ctx.delay(50));
//   });
// };

export const downloadSignedNachForm = () => {
  const url = '*/merchant/api/*/token.registration/paper_mandate/uploaded_form';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          url: '/someurl',
        },
      }),
      ctx.delay(50),
    );
  });
};

export const downloadSignedNachFormWithError = () => {
  const url = '*/merchant/api/*/token.registration/paper_mandate/uploaded_form';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['error', 'Signed Form is not available'],
      }),
      ctx.delay(50),
    );
  });
};

export const resubmitNachForm = () => {
  const url = '*/merchant/api/*/token.registration/paper_mandate/token/*/retry';
  return rest.post(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 1,
          success: true,
        },
      }),
      ctx.delay(50),
    );
  });
};

export const resubmitNachFormWithError = () => {
  const url = '*/merchant/api/*/token.registration/paper_mandate/token/*/retry';
  return rest.post(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['error', 'Error during Resubmit'],
      }),
      ctx.delay(50),
    );
  });
};
