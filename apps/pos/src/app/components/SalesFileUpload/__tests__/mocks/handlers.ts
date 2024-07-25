import { rest } from 'msw';

const UFH_SUCCESS_RESPONSE = {
  status_code: 200,
  success: true,
  data: {
    file_id: 'file_store_id_123',
  },
};

const UFH_FAILURE_RESPONSE = {
  status_code: 500,
  success: false,
  message: 'Internal Server Error',
};

export const uploadFileToUFHHandler = {
  success: () =>
    rest.post('*/ufh/files*', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(UFH_SUCCESS_RESPONSE));
    }),
  failure: () =>
    rest.post('*/ufh/files*', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(UFH_FAILURE_RESPONSE));
    }),
};

const SIGNED_URL_SUCCESS_RESPONSE = {
  status_code: 200,
  success: true,
  data: {
    file_id: 'file_OZnPFIEJFVAB2j',
    signed_url: 'www.someRandomDownloadurl.com',
    name: 'sdsdsd',
    type: 'sales_assisted_onboarding_doc',
    size: 3908,
  },
};

const SIGNED_URL_FAILURE_RESPONSE = {
  status_code: 500,
  success: false,
  errors: ['Internal Server Error'],
};

export const downloadFileHandler = {
  success: () =>
    rest.get('*/ufh/file/*/get-signed-url', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(SIGNED_URL_SUCCESS_RESPONSE));
    }),
  failure: () =>
    rest.get('*/ufh/file/*/get-signed-url', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json(SIGNED_URL_FAILURE_RESPONSE));
    }),
};
