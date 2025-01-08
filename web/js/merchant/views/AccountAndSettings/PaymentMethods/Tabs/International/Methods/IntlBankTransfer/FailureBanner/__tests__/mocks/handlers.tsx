import { rest } from 'msw';

export const failureBannerHandlers = {
  successEddDetails: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'not_verified',
            details: [
              {
                type: 'vkyc',
                status: 'not_verified',
              },
            ],
          },
        }),
      );
    }),
  successVkycApproved: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'initiated',
            details: [
              {
                type: 'vkyc',
                status: 'approved',
              },
            ],
          },
        }),
      );
    }),
  successVkycRejected: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'initiated',
            details: [
              {
                type: 'vkyc',
                status: 'rejected',
              },
            ],
          },
        }),
      );
    }),
  successVkycUnderReview: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'initiated',
            details: [
              {
                type: 'vkyc',
                status: 'under_review',
              },
            ],
          },
        }),
      );
    }),
  successVkycInitiated: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'initiated',
            details: [
              {
                type: 'vkyc',
                status: 'initiated',
              },
            ],
          },
        }),
      );
    }),
  successVkycFraudRejected: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'initiated',
            details: [
              {
                type: 'vkyc',
                status: 'rejected',
                reason: 'customer seems to be a fraud',
              },
            ],
          },
        }),
      );
    }),
  successVkycFailed: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'initiated',
            details: [
              {
                type: 'vkyc',
                status: 'failed',
              },
            ],
          },
        }),
      );
    }),
  successWithoutIecCode: () =>
    rest.get('/merchant/api/live/users/purpose/code', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            merchants: [
              {
                purpose_code: 'purpose_code',
                purpose_code_desc: 'purpose_code_desc',
                iec_code: null,
              },
            ],
          },
        }),
      );
    }),
  successWithoutPurposeCode: () =>
    rest.get('/merchant/api/live/users/purpose/code', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            merchants: [
              {
                purpose_code: null,
                purpose_code_desc: null,
                iec_code: null,
              },
            ],
          },
        }),
      );
    }),
  successPurposeCode: () =>
    rest.get('/merchant/api/live/users/purpose/code', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            merchants: [
              {
                purpose_code: 'purpose_code',
                purpose_code_desc: 'purpose_code_desc',
                iec_code: 'iec_code',
              },
            ],
          },
        }),
      );
    }),
};
