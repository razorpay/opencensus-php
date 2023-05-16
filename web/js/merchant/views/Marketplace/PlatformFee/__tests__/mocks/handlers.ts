import { rest } from 'msw';
import { platformFeeData, platformFeedDetailsData } from './fixtures';

export const platformFeeListSuccess = (response = platformFeeData) => {
  return rest.get('*/merchant/api/*/transfers', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: response,
      }),
      ctx.delay(50),
    );
  });
};

export const platformFeeListError = () => {
  return rest.get('*/merchant/api/*/transfers', (req, res, ctx) => {
    return res(
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['There was an error', 'Status Code: 400'],
      }),
    );
  });
};

export const platformFeeDetailsSuccess = (response) => {
  return rest.get(`*/merchant/api/*/transfers/${platformFeedDetailsData.id}`, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: response,
      }),
    );
  });
};

export const reversalSuccess = (response) => {
  return rest.get(
    `*/merchant/api/*/transfers/${platformFeedDetailsData.id}/reversals`,
    (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: response,
        }),
      );
    },
  );
};
