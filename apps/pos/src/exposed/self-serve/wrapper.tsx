import React from 'react';
import { BladeProvider, Box, ToastContainer } from '@razorpay/blade/components';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { bladeTheme } from '@razorpay/blade/tokens';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import POS from 'apps/pos/src/app/views/SelfServe';
export const queryClient = new QueryClient();

const Wrapper = () => {
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <QueryClientProvider client={queryClient}>
        <ErrorBoundary
          rank={errorService.ErrorRank.P0}
          tags={{ module: MODULES.POS_SELF_SERVE }}
          fallbackComponent={
            <Box marginTop="spacing.8">
              <PageError
                title="Something went wrong!"
                description="We are facing some issues. Please try again later."
              />
            </Box>
          }
        >
          <POS />
        </ErrorBoundary>
      </QueryClientProvider>
      <ToastContainer />
    </BladeProvider>
  );
};

export default Wrapper;
