import React, { lazy, useEffect } from 'react';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import { ErrorBoundary } from '@libs/shared-ui';
import { initAnalytics } from '@libs/shared-utils';
import { BladeProvider, Box, Text } from '@razorpay/blade/components';
import '@razorpay/blade/fonts.css';
import { bladeTheme } from '@razorpay/blade/tokens';
import { I18nProvider, useI18nContext } from '@razorpay/i18nify-react';
import { useQueryClient } from '@tanstack/react-query';
import { LazyMotion } from 'framer-motion';
import ConsentDisclaimer from '../components/ConsentDisclaimer/ConsentDisclaimer';
import fetchSettlementConfig from '../services/api/fetchSettlementConfig';
import FtuxOneHome from './FtuxOneHome/FtuxOneHome';
import { WorkspaceWrapper } from '@federated/apps/shell/connected-navigation/WorkspaceWrapper';

// Framer-motion supporț
const loadFeatures = () =>
  import(/* webpackChunkName: "OneHome" */ '@apps/one-home/src/features').then(
    (res) => res.default,
  );

const CriticalActions = lazy(
  () =>
    import(
      /* webpackChunkName: "OtherInsights" */ '@apps/one-home/src/app/CriticalActions/CriticalActions'
    ),
);

const BusinessSummary = lazy(
  () =>
    import(
      /* webpackChunkName: "BusinessSummary" */ '@apps/one-home/src/app/BusinessSummary/BusinessSummary'
    ),
);

const QuickActions = lazy(
  () =>
    import(
      /* webpackChunkName: "QuickActions" */ '@apps/one-home/src/app/QuickActions/QuickActions'
    ),
);

const PaymentInsights = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentInsights" */ '@apps/one-home/src/app/Insights/PaymentInsights'
    ),
);

const OtherInsights = lazy(
  () =>
    import(/* webpackChunkName: "OtherInsights" */ '@apps/one-home/src/app/Insights/OtherInsights'),
);

const OneHomeContainer = () => {
  const { setI18nState } = useI18nContext();
  useEffect(() => {
    if (setI18nState) {
      setI18nState({
        locale: 'en-IN',
      });
    }
  }, []);

  return (
    <Box display="grid" gap="80px" maxWidth="1200px" margin={['spacing.0', 'auto']}>
      <CriticalActions />
      <QuickActions />
      <BusinessSummary />
      <PaymentInsights />
      <OtherInsights />
    </Box>
  );
};

export const OneHome = () => {
  const queryClient = useQueryClient();

  useEffect(() => {
    const prefetchSettlementConfig = async () => {
      await queryClient.prefetchQuery({
        queryKey: ['settlement_config'],
        queryFn: fetchSettlementConfig,
      });
    };
    prefetchSettlementConfig().catch((error) => {
      console.error('Error prefetching settlement config', error);
    });

    // initialize analytics
    initAnalytics()
      .then(() => {
        if (!window.analytics) {
          console.error('Segment analytics is not available');
          return;
        }

        /**
         * Individual micro-apps can call analytics.identify method which will merge the traits/properties related to the userId
         */
        if (window.analytics?.identify && window.rzp_user) {
          const userId = window.rzp_user?.user.id;

          window.analytics.identify(userId, {
            id: userId,
            userId,
            app: 'ConnectedDashboard',
          });
        }
      })
      .catch((error) => {
        console.error('Error initializing analytics', error);
      });
  }, []);

  return (
    <WorkspaceWrapper isFullPage={true}>
      <I18nProvider>
        <LazyMotion strict features={loadFeatures}>
          <BladeProvider themeTokens={bladeTheme} colorScheme="light">
            <ErrorBoundary
              rank={DASHBOARD_PRIORITY_RANKS.P0}
              team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
              // TODO: Need to have fallback comp
              FallbackComponent={() => (
                <Box marginTop="spacing.8">
                  <Text>Something went wrong!</Text>
                </Box>
              )}
            >
              <Box
                padding="spacing.8"
                borderRadius={{ base: 'none', s: 'none', m: 'large' }}
                borderTopRightRadius={{ base: 'none', s: 'none', m: 'none' }}
                backgroundColor="surface.background.gray.moderate"
              >
                <OneHomeContainer />
              </Box>
              <ConsentDisclaimer />
              <FtuxOneHome />
            </ErrorBoundary>
          </BladeProvider>
        </LazyMotion>
      </I18nProvider>
    </WorkspaceWrapper>
  );
};
