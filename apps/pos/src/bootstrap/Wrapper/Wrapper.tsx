import React, { useEffect } from 'react';
import { BladeProvider, Box, ToastContainer } from '@razorpay/blade/components';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { bladeTheme } from '@razorpay/blade/tokens';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { graphqlClient } from '@federated/apps/shell/graphql';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store';
import { PARTNER_ASSISTED_ONBOARDING } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import App from 'apps/pos/src/app';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import useEnv from 'apps/pos/src/app/utils/hooks/useEnv';
import type { OnboardingWorkflowProduct } from '../../app/types/SalesAssistedOnboarding';

export const queryClient = new QueryClient();

graphqlClient.setHeader('apollographql-client-name', 'pos-assisted-onboarding');

interface WrapperProps {
  workflowProduct?: OnboardingWorkflowProduct;
}

const Wrapper: React.FC<WrapperProps> = ({ workflowProduct }) => {
  const { setWorkflowProduct, setIsPosEkycAgent, setPwaPrompt } = useOnboardingStore();

  const installPromptCallback = (e) => {
    e.preventDefault();
    setPwaPrompt(e); // storing the PWA prompt to zustand store
  };

  useEffect(() => {
    if (typeof window === 'undefined') return;

    window.addEventListener('beforeinstallprompt', installPromptCallback);

    return () => {
      window.removeEventListener('beforeinstallprompt', installPromptCallback);
    };
  }, []);

  React.useEffect(() => {
    if (workflowProduct) {
      setWorkflowProduct(workflowProduct);
      setIsPosEkycAgent(workflowProduct === PARTNER_ASSISTED_ONBOARDING);
    }
  }, [workflowProduct, setWorkflowProduct]);

  const { isProduction, cdnBaseUrl, cdnDashboardAssetsUrl } = useEnv();

  // TODO: Needs to be fixed wrt to new architecture
  React.useEffect(() => {
    const link = document.createElement('link');
    link.rel = 'manifest';
    link.href = isProduction
      ? `${cdnBaseUrl}/static/assets/pos/sales-assisted/manifests/app-prod-manifest.json`
      : `${process.env['UNIVERSE_PUBLIC_ASSETS_URL']}/build/browser/manifest.json`;
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
          tags={{ module: MODULES.SALES_DASHBOARD }}
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
