import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

export const GcmsTestWrapper = ({ children }) => (
  <QueryClientProvider client={queryClient}>
    <BladeProvider themeTokens={paymentTheme}>{children}</BladeProvider>
  </QueryClientProvider>
);
