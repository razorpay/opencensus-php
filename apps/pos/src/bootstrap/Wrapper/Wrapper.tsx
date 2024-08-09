import * as React from 'react';
import { BladeProvider, Box, ToastContainer } from '@razorpay/blade/components';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { bladeTheme } from '@razorpay/blade/tokens';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';

import App from 'apps/pos/src/app';
import PageError from 'apps/pos/src/app/components/PageError';

export const queryClient = new QueryClient();

const isProd = process.env.STAGE == 'production';

const Wrapper = (): JSX.Element => {
  React.useEffect(() => {
    console.log('Injected Manifest!!!!', isProd);
    const link = document.createElement('link');
    link.rel = 'manifest';
    link.href = isProd
      ? `${window.cdnBaseUrl}/static/assets/pos/sales-assisted/manifests/app-prod-manifest.json`
      : `${process.env.UNIVERSE_PUBLIC_ASSETS_URL}/build/browser/manifest.json`;
    document.head.appendChild(link);
    return () => {
      document.head.removeChild(link);
    };
  }, []);
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <QueryClientProvider client={queryClient}>
        <ErrorBoundary
          rank={errorService.ErrorRank.P0}
          tags={{ module: 'assisted-pos-onboarding' }}
          fallbackComponent={
            <Box marginTop="spacing.8">
              <PageError
                title="Something went wrong!"
                description="We are facing some issues. Please try again later."
              />
            </Box>
          }
        >
          <App />
        </ErrorBoundary>
      </QueryClientProvider>
      <ToastContainer />
    </BladeProvider>
  );
};

export default Wrapper;
