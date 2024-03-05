import { MockedRequest, rest } from 'msw';
import { server } from 'test-utils';

import { FetchProductBySlugRequest } from 'merchant/views/RizeMarketplace/common/types';

import { mockData } from './fixtures';

export const mockFetchProductBySlugAPIResponse = ({ error }: { error?: boolean }) => {
  return server.use(
    rest.post(
      '*/merchant/api/:mode/rize/dashboard/fetch_product_by_slug',
      (req: MockedRequest<FetchProductBySlugRequest>, res, ctx) => {
        if (error) {
          return res(
            ctx.status(200),
            ctx.json({
              errors: [],
              status_code: 500,
              success: false,
            }),
            ctx.delay(50),
          );
        }

        const product = mockData.find((product) => product.slug === req.body.slug);

        if (!product) {
          return res(
            ctx.status(200),
            ctx.json({
              data: {
                downstream_status_code: 404,
              },
              status_code: 200,
              success: true,
            }),
            ctx.delay(50),
          );
        }

        return res(
          ctx.status(200),
          ctx.json({
            data: {
              ...product,
              downstream_status_code: 200,
            },
            status_code: 200,
            success: true,
          }),
          ctx.delay(50),
        );
      },
    ),
  );
};
