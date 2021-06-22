import { rest, graphql } from 'msw';
import * as ActivationDB from '../v2/merchant/onboarding/mobile/services/data/ActivationDB';
import * as PaymentsDB from '../v2/merchant/onboarding/mobile/services/data/PaymentsDB';
import * as WebsiteWorkflowDB from '../v2/merchant/onboarding/mobile/services/data/WebsiteWorkflowDB';
import * as InternationalWorkflowDB from '../v2/merchant/onboarding/mobile/services/data/InternationalWorkflowDB';
import * as BusinessCategoryDB from '../v2/merchant/onboarding/mobile/services/data/BusinessCategoryDB';
import * as PaymentEscalationDB from '../v2/merchant/onboarding/mobile/services/data/PaymentEscalationDB';

export const handlers = [
  // Handles a "Login" mutation
  graphql.mutation('Login', (req, res, ctx) => {
    const { username } = req.variables;
    sessionStorage.setItem('is-authenticated', username);

    return res(
      ctx.data({
        login: {
          username,
        },
      }),
    );
  }),

  // Handles a "GetUserInfo" query
  graphql.query('GetUserInfo', (req, res, ctx) => {
    const authenticatedUser = sessionStorage.getItem('is-authenticated');

    if (!authenticatedUser) {
      // When not authenticated, respond with an error
      return res(
        ctx.errors([
          {
            message: 'Not authenticated',
            errorType: 'AuthenticationError',
          },
        ]),
      );
    }

    // When authenticated, respond with a query payload
    return res(
      ctx.data({
        user: {
          username: authenticatedUser,
          firstName: 'John',
        },
      }),
    );
  }),

  rest.get(
    'http://localhost:6006/merchant/api/test/merchant/activation/business_details',
    (req, res, ctx) => {
      const search_string = req.url.searchParams.get('search_string');
      return res(
        ctx.status(200),
        ctx.delay(500),
        ctx.json({
          status_code: 200,
          success: true,
          data: BusinessCategoryDB.read().filter((item) => {
            let matched = false;
            item.matches.forEach((_item) => {
              if (_item.subcategory_name.includes(search_string)) {
                matched = true;
              }
            });
            return matched;
          }),
        }),
      );
    },
  ),

  rest.get('http://localhost:6006/merchant/api/live/merchant/activation', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        success: true,
        data: ActivationDB.read(),
      }),
    );
  }),

  rest.post('http://localhost:6006/merchant/api/live/merchant/activation', (req, res, ctx) => {
    if (req.body) {
      if (req.body.submit) {
        req.body.submitted = true;
      }
      if (req.body.business_subcategory === 'computer_software_stores') {
        req.body.activation_flow = 'blacklist';
      } else if (req.body.business_subcategory) {
        req.body.activation_flow = 'whitelist';
      }
      ActivationDB.update(req.body);
    }

    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: ActivationDB.read(),
      }),
    );
  }),

  rest.get('http://localhost:6006/merchant/api/test/payments', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: PaymentsDB.read(),
      }),
    );
  }),

  rest.get(
    'http://localhost:6006/merchant/api/live/merchants/product_international/workflow/status/all',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: { data: InternationalWorkflowDB.read() },
        }),
      );
    },
  ),

  rest.get(
    'http://localhost:6006/merchant/api/live/merchant/activation/websites/status',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          ...WebsiteWorkflowDB.read(),
        }),
      );
    },
  ),

  rest.post(
    'http://localhost:6006/merchant/api/live/merchant/documents/upload',
    (req, res, ctx) => {
      if (req.body) {
        ActivationDB.update({
          documents: {
            aadhar_back: [
              {
                id: 'Fz4zHZJgOUHbGs',
                file_store_id: 'Fz4zHkwuXILcgw',
                merchant_id: 'FguKGQ2MICXBFc',
              },
            ],
          },
        });
      }

      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: ActivationDB.read(),
        }),
      );
    },
  ),

  rest.delete(
    `http://localhost:6006/merchant/api/live/merchant/documents/doc_:params`,
    (req, res, ctx) => {
      const docId = req.url.pathname.split('doc_')[1];
      if (docId) {
        ActivationDB.update({
          documents: {
            business_pan_url: null,
          },
        });
      }

      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: ActivationDB.read(),
        }),
      );
    },
  ),

  rest.post(
    'http://localhost:6006/merchant/api/test/bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarGetCaptcha',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: {
            captcha_image:
              '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',
            sessionExpired: false,
          },
        }),
      );
    },
  ),

  rest.post(
    'http://localhost:6006/merchant/api/test/bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarVerifyCaptchaAndSendOtp',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: {
            is_success: true,
          },
        }),
      );
    },
  ),

  rest.post(
    'http://localhost:6006/merchant/api/test/bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarSubmitOtp',
    (req, res, ctx) => {
      let response = {
        status_code: 200,
        data: {
          is_valid: true,
        },
      };
      if (req.body.otp != '123456') {
        response = {
          status_code: 200,
          data: {
            error_code: 'INCORRECT_OTP',
            error_description:
              ' hyperverge gateway request failed with http code - 400  internal code  - 11203, error - OTP/TOTP Fail 2 attempts remaining.',
          },
        };
      }
      return res(ctx.status(200), ctx.delay(1000), ctx.json(response));
    },
  ),

  rest.get(
    'http://localhost:6006/merchant/api/live/merchants/onboarding/escalations',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: { ...PaymentEscalationDB.read() },
        }),
      );
    },
  ),
];
