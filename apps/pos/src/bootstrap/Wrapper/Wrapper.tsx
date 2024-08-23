import * as React from 'react';
import { BladeProvider, Box, ToastContainer } from '@razorpay/blade/components';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { bladeTheme } from '@razorpay/blade/tokens';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { graphqlClient } from '@dashboard/shared-utils/graphql/graphql';
import App from 'apps/pos/src/app';
import PageError from 'apps/pos/src/app/components/PageError';
import useEnv from 'apps/pos/src/app/utils/hooks/useEnv';

export const queryClient = new QueryClient();
graphqlClient.setHeader(
  'apollographql-client-name',
  process.env.UNIVERSE_PUBLIC_APP_NAME as string,
);

const Wrapper = (): JSX.Element => {
  const { isProduction, cdnBaseUrl } = useEnv();

  React.useEffect(() => {
    console.log('Injected Manifest!!!!', { isProduction });
    const link = document.createElement('link');
    link.rel = 'manifest';
    link.href = isProduction
      ? `${cdnBaseUrl}/static/assets/pos/sales-assisted/manifests/app-prod-manifest.json`
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
