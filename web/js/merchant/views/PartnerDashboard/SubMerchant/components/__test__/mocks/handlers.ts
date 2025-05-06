import { rest } from 'msw';

import {
  smsResponse,
  uploadStatementResponse,
  submitStatementResponse,
  requestKycResponse,
} from './fixtures';

export const sendMessageSuccess = (response = smsResponse) => {
  return rest.post(
    '*/partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/CommunicateBureauLink',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(response), ctx.delay(50));
    },
  );
};

export const sendMessageError = () => {
  return rest.post(
    '*/partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/CommunicateBureauLink',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['There was an error', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    },
  );
};

export const uploadBankStatementSuccess = (response = uploadStatementResponse) => {
  return rest.post(
    '*/los/service/twirp/rzp.capital.los.origination.v1.ApplicationAPI/UploadDocumentToStore',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(response), ctx.delay(50));
    },
  );
};

export const uploadBankStatementError = () => {
  return rest.post(
    '*/los/service/twirp/rzp.capital.los.origination.v1.ApplicationAPI/UploadDocumentToStore',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['There was an error', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    },
  );
};

export const submitBankStatementSuccess = (response = submitStatementResponse) => {
  return rest.post(
    '*/partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/UploadDocuments',
    (req, res, ctx) => {
      return res(ctx.status(200), ctx.json(response), ctx.delay(50));
    },
  );
};

export const submitBankStatementError = () => {
  return rest.post(
    '*/partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/UploadDocuments',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 400,
          success: false,
          errors: ['There was an error', 'Status Code: 400'],
        }),
        ctx.delay(50),
      );
    },
  );
};

export const sendKycRequestSuccess = (response = requestKycResponse) => {
  return rest.post('*/partner/kyc_access_request', (req, res, ctx) => {
    return res(ctx.status(200), ctx.json(response), ctx.delay(50));
  });
};

export const sendKycRequestError = () => {
  return rest.post('*/partner/kyc_access_request', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['There was an error', 'Status Code: 400'],
      }),
      ctx.delay(50),
    );
  });
};
