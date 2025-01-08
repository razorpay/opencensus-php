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

export const virtualAccountsHandlers = {
  success: () =>
    rest.get('/merchant/api/live/international/virtual_accounts', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            accounts: [],
          },
        }),
      );
    }),
  successAccounts: () =>
    rest.get('/merchant/api/live/international/virtual_accounts', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            status: 'activated',
            accounts: [
              {
                va_currency: 'USD',
                routing_code: 'routing_code',
                routing_type: 'routing_type',
                account_number: 'account_number',
                beneficiary_name: 'beneficiary_name',
                bank_name: 'bank_name',
                bank_address: 'bank_address',
                status: 'activated',
              },
            ],
          },
        }),
      );
    }),
  successAccountDeactivated: () =>
    rest.get('/merchant/api/live/international/virtual_accounts', (_req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          data: {
            accounts: [
              {
                va_currency: 'USD',
                routing_code: 'routing_code',
                routing_type: 'routing_type',
                account_number: 'account_number',
                beneficiary_name: 'beneficiary_name',
                bank_name: 'bank_name',
                bank_address: 'bank_address',
                status: 'deactivated',
              },
            ],
          },
        }),
      );
    }),
  failure: () =>
    rest.get('/merchant/api/live/edd_details', (_req, res, ctx) => {
      return res(
        ctx.status(500),
        ctx.json({
          error: {
            status: 500,
            description: 'Internal Server Error',
          },
        }),
      );
    }),
};
