import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { rest } from 'msw';
import { setupServer } from 'msw/node';

import { render } from 'test-utils';

export const server = setupServer();

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      cacheTime: 0,
      retry: false,
    },
  },
});

export const renderWithQueryClient = (component, ...rest) =>
  render(<QueryClientProvider client={queryClient}>{component}</QueryClientProvider>, ...rest);

export const purposeCodeHandlers = {
  success: () =>
    rest.get('/merchant/api/live/purposecode', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: [
            {
              purposeGroup: 'group-1',
              codes: [
                {
                  purposeCode: 'P0101',
                  description: 'description',
                },
                {
                  purposeCode: 'P0102',
                  description: 'description',
                },
              ],
            },
            {
              purposeGroup: 'group-2',
              codes: [
                {
                  purposeCode: 'P0201',
                  description: 'description',
                },
                {
                  purposeCode: 'P0202',
                  description: 'description',
                },
              ],
            },
          ],
        }),
      );
    }),
  patchPurposeCode: () =>
    rest.patch('/merchant/api/live/merchants/purpose/code', (_req, res, ctx) => {
      return res(ctx.status(200), ctx.json({ data: { purposeCode: 'P0101' } }));
    }),
  postGenerateLink: () =>
    rest.post('/merchant/api/live/vkyc', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({ data: { details: { weblink: 'http://example.com' } } }),
      );
    }),
};
