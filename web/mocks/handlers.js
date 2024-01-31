import { rest, graphql } from 'msw';
import { paymentHandlers } from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/handlers';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as PaymentsDB from 'merchant/views/onboarding/mobile/services/data/PaymentsDB';
import * as WebsiteWorkflowDB from 'merchant/views/onboarding/mobile/services/data/WebsiteWorkflowDB';
import * as InternationalWorkflowDB from 'merchant/views/onboarding/mobile/services/data/InternationalWorkflowDB';
import * as BusinessCategoryDB from 'merchant/views/onboarding/mobile/services/data/BusinessCategoryDB';
import * as PaymentEscalationDB from 'merchant/views/onboarding/mobile/services/data/PaymentEscalationDB';
import * as GstinDetailsDB from 'merchant/views/onboarding/mobile/services/data/GstinDetailsDB';
import * as TermsAndConditionDB from 'merchant/views/TermsAndCondition/services/TermsAndConditionDB';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';
import WEBHOOK_HANDLERS from 'merchant/views/Settings/Webhooks/__test__/mocks/handlers';
import logHandlers from 'merchant_common/containers/ReportsAsync/Logs/__test__/mocks/handlers';
import { payoutDetailsHandlers } from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/__test__/mocks/handlers';
import { instantDetailsHandlers } from 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails/__test__/mocks/handlers';
import reportsHandlers from 'merchant_common/containers/ReportsAsync/__test__/mocks/handlers';
import { paymentPagesHandlers } from '../js/merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';
import ONDEMAND_SETTLEMENTS_HANDLERS from 'merchant/views/Settlements/Settlements/components/__test__/mocks/handlers';
import {
  keyHandlers,
  pluginHandlers,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/handlers';
import { paymentHandleHandlers } from '../js/merchant/containers/Home/ProductOnboardingCard/__test__/mocks/handlers';
import {
  partnerConfigFetchHandlers,
  partnerConfigSaveHandlers,
} from 'merchant/views/PartnerDashboard/Settings/configuration/__tests__/mocks/handlers';
import { subMerchantListHandlers } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/handlers';
import { instrumentHandlers } from 'merchant/views/AccountAndSettings/PaymentMethods/__test__/mocks/handlers';
import batchHandler from 'merchant/views/Wallet/BatchActions/__tests__/mocks/handlers';
import { newAuthHandler } from 'newAuth/signup/components/PartnerSignup/__test__/mocks/handlers';
import walletFundsHandlers from 'merchant/views/Wallet/Funds/Transactions/__tests__/mocks';
import walletTransactionHandlers from 'merchant/views/Wallet/Transactions/__tests___/mocks';
import accountDetailHandlers from 'merchant/views/Wallet/AccountDetail/__tests__/mocks/index';
import walletLoadsHandlers from 'merchant/views/Wallet/Loads/__tests__/mocks/index';
import walletPaymentHandlers from 'merchant/views/Wallet/Payments/__tests__/mocks/index';
import walletReportHandlers from 'merchant/views/Wallet/Reports/__tests__/mocks/handlers';
import { commisionsHandler } from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/handlers';
import { submerchantKYCHandlers } from 'merchant/views/PartnerDashboard/SubMerchant/KYC/__tests__/mocks/handlers';
import { codEngineHandlers } from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/handlers';
import { magicOrderAnalyticsHandler } from 'merchant/views/MagicCheckout/OrderAnalytics/__tests__/mocks/handlers';
import { partnerActivationHandler } from 'merchant/views/PartnerDashboard/Activation/__tests__/mocks/handlers';
import { magicShopifyOrderEditingHandler } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/__test__/mocks/handlers';
import { magicCouponEngineHandler } from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/handlers';
import { magicShippingEngineHandlers } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/handlers';
import gcmsBrandAccountHandler from 'merchant/views/GCMS/Funds/BrandAccount/__tests__/mocks';
import gcmsResellerAccountsHandler from 'merchant/views/GCMS/Funds/ResellerAccounts/__tests__/mocks';
import gcmsProgramsHandler from 'merchant/views/GCMS/Programs/__tests__/mocks';
import gcmsOrdersHandler from 'merchant/views/GCMS/Orders/__tests__/mocks';
import gcmsResellersHandler from 'merchant/views/GCMS/Resellers/__tests__/mocks';

export const handlers = [
  ...batchHandler,
  ...walletFundsHandlers,
  ...walletTransactionHandlers,
  ...accountDetailHandlers,
  ...walletLoadsHandlers,
  ...walletPaymentHandlers,
  ...walletReportHandlers,

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

  rest.get('*/merchant/onboarding/business_types', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          registered: [
            {
              label: 'LLP',
              id: '6',
              status: 'active',
            },
            {
              label: 'Partnership',
              id: '3',
              status: 'active',
            },
            {
              label: 'Private Limited',
              id: '4',
              status: 'active',
            },
            {
              label: 'Proprietorship',
              id: '1',
              status: 'inactive',
            },
            {
              label: 'Trust',
              id: '9',
              status: 'active',
            },
          ],
          unregistered: [
            {
              label: 'Individual',
              id: '2',
              status: 'active',
            },
            {
              label: 'Unregistered',
              id: '11',
              status: 'active',
            },
          ],
        },
      }),
    );
  }),

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
      let response = {
        status_code: 200,
        data: {
          is_success: true,
        },
      };

      if (req.body.aadhaar_number !== '945252561004') {
        response = {
          status_code: 200,
          data: {
            error_code: 'NO_PROVIDER_ERROR',
            error_description:
              'hyperverge gateway request failed with http code - 400  internal code  - 11203, error - hyperverge is down',
          },
        };
      }

      return res(ctx.status(200), ctx.delay(1000), ctx.json(response));
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
      } else if (req.body.otp === '123452') {
        response = {
          status_code: 200,
          data: {
            error_code: 'NO_PROVIDER_ERROR',
            error_description:
              'hyperverge gateway request failed with http code - 400  internal code  - 11203, error - hyperverge is down',
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

  rest.get(
    'http://localhost:6006/merchant/api/live/merchants/activation/gst_details',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: { ...GstinDetailsDB.read() },
        }),
      );
    },
  ),

  rest.post('http://localhost:6006/merchant/api/live/merchant/tnc', (req, res, ctx) => {
    if (req.body) {
      TermsAndConditionDB.update(req.body);
    }

    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: TermsAndConditionDB.read(),
      }),
    );
  }),

  rest.get(`http://localhost:6006/merchant/api/test/pincodes/530068`, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: { city: 'Noida', state: 'UP' },
      }),
    );
  }),

  rest.get(`*/merchant/api/test/merchant/aov-config`, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: {
          config: [
            { min: 1, max: 150 },
            { min: 151, max: 300 },
            { min: 301, max: 600 },
            { min: 601, max: 1000 },
            { min: 1001, max: 2000 },
            { min: 2001, max: 3000 },
            { min: 3001, max: 5000 },
            { min: 5001, max: 10000 },
            { min: 10001, max: 20000 },
            { min: 20001, max: 50000 },
            { min: 50001, max: 100000 },
            { min: 100001, max: 0 },
          ],
        },
      }),
    );
  }),

  rest.post('*/merchant/api/live/merchant/activation/otp/send', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: { token: 'dstrj34adf' },
      }),
    );
  }),

  rest.post('*/merchant/api/test/users/verify_email', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: {
          confirmed: true,
          email: 'abc@gmail.com',
          email_verified: true,
          signup_via_email: 0,
        },
      }),
    );
  }),

  // Settlements
  rest.get('*/merchant/api/test/settlements/:id', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: SettlementsDB.settlementsInfo,
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/api/test/settlements/:id/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: {
          entity: 'collection',
          count: 3,
          items: SettlementsDB.settlementTabBreakupDetails.items,
        },
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/api/test/settlement/holidays', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: SettlementsDB.holidaysList,
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/api/test/schedule_tasks/settlement', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: SettlementsDB.schedule,
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/api/test/es/scheduled_pricing', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data: SettlementsDB.scheduledPricing,
      }),
      ctx.delay(50),
    );
  }),

  rest.post('*/merchant/api/test/es/scheduled', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
      }),
      ctx.delay(50),
    );
  }),

  rest.post('*/merchant/api/test/settlements/:id/transaction_source_details', (req, res, ctx) => {
    let data = SettlementsDB.settlementsListData;

    if (req.body.limit === '5' || req.body.skip === 10) {
      data = SettlementsDB.settlementsListData.slice(0, 5);
    } else if (req.body.source_type === 'refund') {
      data = SettlementsDB.settlementsListRefundData;
    } else if (
      req.body.source_id === SettlementsDB.settlementsListData[0].id ||
      (req.body.source_type === 'settlement.ondemand' && !!req.body.source_id)
    ) {
      data = [SettlementsDB.settlementsListData[0]];
    } else if (req.body.source_id === 'no-results') {
      data = [];
    } else if (req.body.source_id === 'error-input') {
      return res(
        ctx.status(200),
        ctx.json({
          success: false,
          errors: ['failed to load transaction source details'],
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        data,
      }),
      ctx.delay(50),
    );
  }),

  rest.post('*/merchant/api/:mode/settlements/amount_check', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        data: {
          settlementAmount: 309,
          totalTransactionAmount: -1036.67,
        },
      }),
      ctx.delay(50),
    );
  }),

  // Bank account
  rest.get('https://ifsc.razorpay.com/:ifscCode', (req, res, ctx) => {
    const { ifscCode } = req.params;

    if (ifscCode === 'ICIC0003714') {
      return res(
        ctx.status(200),
        ctx.json({
          BANK: 'ICIC Bank',
          BRANCH: 'Aundh, Pune',
        }),
        ctx.delay(50),
      );
    }

    if (ifscCode === 'IDFC0003715') {
      return res.networkError('Failed to connect');
    }

    return res(
      ctx.status(200),
      ctx.json({
        BANK: 'HDFC Bank',
        BRANCH: '',
      }),
      ctx.delay(50),
    );
  }),

  rest.post('*/merchant/api/test/merchants/bank_account/file/upload', (req, res, ctx) => {
    if (req.body.get('address_proof_url') === 'VALID_FILE') {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
        }),
        ctx.delay(50),
      );
    }

    return res(ctx.errors([{ message: 'Some error occurred' }]), ctx.delay(50));
  }),

  // Verify Email
  rest.post('*/merchant/api/live/users/email/update/verify', (req, res, ctx) => {
    if (req.body.otp === '000000' || req.body.otp === '111111') {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: false,
          errors: req.body.otp === '000000' ? ['incorrect-otp'] : '',
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
      }),
      ctx.delay(50),
    );
  }),

  // Verify Mobile
  rest.post('*/merchant/api/live/users/verify/mode/sms', (req, res, ctx) => {
    if (req.body.otp === '000000' || req.body.otp === '111111') {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: false,
          errors: req.body.otp === '000000' ? ['incorrect-otp'] : '',
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { otp_auth_token: 'test' },
      }),
      ctx.delay(50),
    );
  }),

  // Send OTP to mobile
  rest.post('*/merchant/api/live/users/otp/send', (req, res, ctx) => {
    if (req.body.otp === '000000' || req.body.otp === '111111') {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: false,
          errors: req.body.otp === '000000' ? ['incorrect-otp'] : '',
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { token: 'test' },
      }),
      ctx.delay(50),
    );
  }),

  // Send OTP to email and Add Email
  rest.post('*/merchant/api/live/users/email/update', (req, res, ctx) => {
    const email = req.body.email;
    if (
      email === 'error@razorpay.com' ||
      email.includes('incorrect-email') ||
      email.includes('no-error-message')
    ) {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: false,
          errors:
            email === 'error@razorpay.com'
              ? ['error-email']
              : email.includes('incorrect-email')
              ? ['incorrect-email']
              : '',
        }),
        ctx.delay(50),
      );
    }

    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
      }),
      ctx.delay(50),
    );
  }),

  // Fetch User info
  rest.get('*/user', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          current: '',
        },
      }),
      ctx.delay(50),
    );
  }),

  // Fetch merchant features
  rest.get('*/merchants/me/features', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          features: [],
        },
      }),
      ctx.delay(50),
    );
  }),

  // generic handler to be used across app
  rest.get('*/merchant/api/test/balance', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 'HFwYCSNr1Ke0rC',
          merchant_id: 'HFQ3S14NsDs3Ti',
          type: 'primary',
          currency: 'INR',
          name: null,
          balance: 990000000,
          credits: 0,
          fee_credits: 0,
          refund_credits: 0,
          account_number: null,
          account_type: null,
          channel: null,
          updated_at: 1643017682,
          locked_balance: 0,
          last_fetched_at: null,
        },
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/is_admin_as_merchant', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          data: {
            is_admin_as_merchant: false,
          },
        },
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/org_settlements/:settlementId', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          data: {
            org_settlement: {},
          },
        },
      }),
      ctx.delay(50),
    );
  }),

  rest.get('*/merchant/:id/tnc', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          data: {},
        },
      }),
      ctx.delay(50),
    );
  }),

  ...ONDEMAND_SETTLEMENTS_HANDLERS,
  ...WEBHOOK_HANDLERS,

  ...paymentHandlers,
  ...logHandlers,
  ...payoutDetailsHandlers,
  ...instantDetailsHandlers,
  ...reportsHandlers,
  ...paymentPagesHandlers,
  ...keyHandlers,
  ...pluginHandlers,
  ...paymentHandleHandlers,
  ...partnerConfigFetchHandlers,
  ...partnerConfigSaveHandlers,
  ...subMerchantListHandlers,
  ...instrumentHandlers,
  ...newAuthHandler,
  ...commisionsHandler,
  ...submerchantKYCHandlers,
  ...codEngineHandlers,
  ...magicOrderAnalyticsHandler,
  ...partnerActivationHandler,
  ...magicShopifyOrderEditingHandler,
  ...magicCouponEngineHandler,
  ...magicShippingEngineHandlers,
  ...gcmsBrandAccountHandler,
  ...gcmsResellerAccountsHandler,
  ...gcmsProgramsHandler,
  ...gcmsOrdersHandler,
  ...gcmsResellersHandler,
];
