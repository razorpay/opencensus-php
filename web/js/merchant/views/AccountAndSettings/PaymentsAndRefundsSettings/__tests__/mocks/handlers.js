import { rest } from 'msw';

export const fetchFeatureByNameHandler = (userId, featureName) => {
  return rest.get(
    `*/merchant/api/test/feature/merchant/${userId}/${featureName}`,
    (req, res, ctx) => {
      return res.once(
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            status: false,
          },
        }),
        ctx.delay(50),
      );
    },
  );
};
