import { rest } from 'msw';

export const mockMainPageSubmitSuccess = () =>
  rest.post('*/UpdateMerchantWebsite', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          current_status: 'in_progress',
        },
      }),
    );
  });

export const mockMainPageSubmitLivenessCheckFailure = () =>
  rest.post('*/UpdateMerchantWebsite', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: false,
        errors: ['main_page verification liviness check failed with status code as xx'],
      }),
    );
  });

export const mockMainPageSubmitFailure = () =>
  rest.post('*/UpdateMerchantWebsite', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: false,
      }),
    );
  });

export const mockAppSubmitSuccess = () =>
  rest.post('*/merchant/save_business_website/app', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: true,
        data: {},
      }),
    );
  });

export const mockAppSubmitFailure = () =>
  rest.post('*/merchant/save_business_website/app', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: false,
        errors: ['Something went wrong. Please try again.'],
      }),
    );
  });

export const mockNonActivatedSubmitSuccess = () =>
  rest.put('*/merchant/activation/update_website_details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          business_website: 'https://www.gurusakhi.com',
          has_key_access: true,
        },
      }),
    );
  });

export const mockNonActivatedSubmitFailure = () =>
  rest.put('*/merchant/activation/update_website_details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: false,
      }),
    );
  });

export const mockPolicyPageSubmitSuccessWithWorkflow = () =>
  rest.post('*/UpdateMerchantWebsite', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          current_status: 'workflow_in_progress',
          website_verification_stage: {
            worklfow_exist: true,
          },
        },
      }),
    );
  });

export const mockPolicyPageSubmitSuccess = () =>
  rest.post('*/UpdateMerchantWebsite', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          current_status: 'completed',
          website_verification_stage: {
            worklfow_exist: false,
          },
        },
      }),
    );
  });

export const mockPolicyPageSubmitFailure = () =>
  rest.post('*/UpdateMerchantWebsite', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(0),
      ctx.json({
        status_code: 200,
        success: false,
      }),
    );
  });
