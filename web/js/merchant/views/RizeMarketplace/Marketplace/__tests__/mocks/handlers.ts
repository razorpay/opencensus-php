import { MockedRequest, rest } from 'msw';
import { server } from 'test-utils';

import { FetchProductsRequest } from 'merchant/views/RizeMarketplace/common/types';

import { mockData } from './fixtures';

export const mockFetchProductsAPIResponse = ({ error }: { error?: boolean }) => {
  return server.use(
    rest.post(
      '*/merchant/api/:mode/rize/dashboard/fetch_products',
      (req: MockedRequest<FetchProductsRequest>, res, ctx) => {
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

        const { filters, search } = req.body;
        const results = mockData
          .filter((product) =>
            !filters?.category.length ? true : filters.category.includes(product.category),
          )
          .filter((product) => (!search ? true : product.name.toLowerCase().includes(search)));

        return res(
          ctx.status(200),
          ctx.json({
            data: {
              total_count: results.length,
              results,
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
