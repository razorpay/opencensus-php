import { rest, graphql } from 'msw';
import * as ActivationDB from '../v2/merchant/onboarding/mobile/services/data/ActivationDB';
import * as PaymentsDB from '../v2/merchant/onboarding/mobile/services/data/PaymentsDB';
import * as WebsiteWorkflowDB from '../v2/merchant/onboarding/mobile/services/data/WebsiteWorkflowDB';
import * as InternationalWorkflowDB from '../v2/merchant/onboarding/mobile/services/data/InternationalWorkflowDB';
import * as BusinessCategoryDB from '../v2/merchant/onboarding/mobile/services/data/BusinessCategoryDB';

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

  rest.get('http://localhost:6006/activation/business_details', (req, res, ctx) => {
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
          if (matched) {
            return true;
          }
          return false;
        }),
      }),
    );
  }),

  rest.get('http://localhost:6006/activation', (req, res, ctx) => {
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

  rest.post('http://localhost:6006/activation', (req, res, ctx) => {
    if (req.body) {
      const updatedValues = Object.keys(req.body).reduce((prev, cur) => {
        return (prev = { ...prev, [cur]: req.body[cur].value });
      }, {});
      ActivationDB.update(updatedValues);
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

  rest.get('http://localhost:6006/payments', (req, res, ctx) => {
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
    'http://localhost:6006/merchants/product_international/workflow/status/all',
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.delay(50),
        ctx.json({
          status_code: 200,
          data: InternationalWorkflowDB.read(),
        }),
      );
    },
  ),

  rest.get('http://localhost:6006/merchant/activation/websites/status', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(50),
      ctx.json({
        status_code: 200,
        data: WebsiteWorkflowDB.read(),
      }),
    );
  }),
];
