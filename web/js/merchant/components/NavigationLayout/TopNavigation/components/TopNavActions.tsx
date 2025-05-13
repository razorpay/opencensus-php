import { LayerProvider } from 'common/components/Layer/LayerContext';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import {
  SearchInput,
  Tooltip,
  Button,
  ActivityIcon,
  AnnouncementIcon,
  Avatar,
  BladeProvider,
  useTheme,
  Skeleton,
  Box,
} from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { I18nProvider } from '@razorpay/i18nify-react';
import { I18ServiceProvider } from '@federated/dashboards/payments/services/i18Service';
import { SpiltzServiceProvider } from '@federated/dashboards/payments/services/splitzService';
import { Provider } from 'react-redux';
import { ShellZustandToReduxSyncProvider } from 'common/utils/store-sync';
import store from '@federated/dashboards/payments/store';
import { useStore } from '@federated/apps/shell/commonStore';
import React, { Suspense, lazy } from 'react';
import { usePaymentsTopNavLoading } from '../../hooks/usePaymentsTopNavLoading';
import { useBreakpoint } from '@razorpay/blade/utils';
import { matchPath, useLocation } from 'react-router-dom';
import { shouldDisplaySearchBasedOnDeviceType, isBillMeOnlyUser } from '@libs/shared-utils';

const ConnectedUniversalSearchWrapper = lazy(() => import('./ConnectedUniversalSearchWrapper'));
const EcosystemDowntimes = lazy(() => import('@dashboards/payments/views/EcosystemDowntimes'));
const ConnectedAnnouncements = lazy(() => import('./ConnectedAnnouncements'));
const ConnectedPaymentsProfileDropdownWrapper = lazy(
  () => import('./ConnectedPaymentsProfileDropdownWrapper'),
);
const TopLevelModals = lazy(() => import('../TopLevelModals'));

type SkeletonLoaderProps = {
  showOnlyMobileSearch?: boolean;
};

const SkeletonLoader = ({ showOnlyMobileSearch }: SkeletonLoaderProps) => {
  const user = useStore((state) => state.session.user);
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const location = useLocation();
  const currentPath = location.pathname;

  const isPartnersPathActive = Boolean(matchPath({ path: '/partners/*' }, currentPath));

  const isMobile = matchedDeviceType === 'mobile';

  const isBillMeOnlyMerchant = Boolean(window.IS_BILL_ME_ENABLED) && isBillMeOnlyUser(user);

  const shouldShowSearchSkeleton =
    !isPartnersPathActive &&
    !isBillMeOnlyMerchant &&
    shouldDisplaySearchBasedOnDeviceType({
      isMobile,
      showOnlyMobileSearch,
    });

  if (showOnlyMobileSearch) {
    return (
      <Box display="flex" alignItems="center" gap="spacing.5" height="40px">
        <Skeleton width="100%" height="32px" borderRadius="medium" />
      </Box>
    );
  }

  return (
    <BladeProvider themeTokens={bladeTheme}>
      <Box display="flex" alignItems="center" gap="spacing.5" height="40px">
        {shouldShowSearchSkeleton && <Skeleton width="200px" height="32px" borderRadius="medium" />}
        <Box display="flex" gap="spacing.4" marginLeft={'auto'}>
          <Skeleton width="32px" height="32px" borderRadius="medium" />
          <Skeleton width="32px" height="32px" borderRadius="medium" />
          <Skeleton width="32px" height="32px" borderRadius="medium" />
        </Box>
      </Box>
    </BladeProvider>
  );
};

type TopNavActionsProps = {
  showOnlyMobileSearch?: boolean;
};

function TopNavActions({ showOnlyMobileSearch }: TopNavActionsProps) {
  const user = useStore((state) => state.session.user);
  const { isTopNavActionsLoading } = usePaymentsTopNavLoading();
  const location = useLocation();
  const currentPath = location.pathname;

  const isHomePath = currentPath.startsWith('/home');

  const showEcosystemDowntimeButton =
    user?.isOrgRZP && user?.isEcosystemDowntimeEnabled && user.isCountryIndia && !isHomePath;

  const getTopNavActionComponents = () => {
    if (isTopNavActionsLoading) {
      return <SkeletonLoader showOnlyMobileSearch={showOnlyMobileSearch} />;
    }

    if (showOnlyMobileSearch) {
      return (
        <Suspense
          fallback={
            <Box display="flex" alignItems="center" gap="spacing.5" height="40px">
              <Skeleton width="100%" height="32px" borderRadius="medium" />
            </Box>
          }
        >
          <ConnectedUniversalSearchWrapper showOnlyMobileSearch={showOnlyMobileSearch} />
        </Suspense>
      );
    }

    return (
      <>
        <Suspense fallback={<Skeleton width="200px" height="32px" borderRadius="medium" />}>
          <ConnectedUniversalSearchWrapper />
        </Suspense>
        {showEcosystemDowntimeButton && (
          <LayerProvider layerHostPosition="absolute">
            <Suspense fallback={<Skeleton width="32px" height="32px" borderRadius="medium" />}>
              <EcosystemDowntimes
                mode={'live'}
                showMobileNav={false}
                isRTUXHomepage={false}
                isConnectedNavigation={true}
              />
            </Suspense>
          </LayerProvider>
        )}

        <Suspense fallback={<Skeleton width="32px" height="32px" borderRadius="medium" />}>
          <ConnectedAnnouncements />
        </Suspense>

        <Suspense fallback={<Skeleton width="32px" height="32px" borderRadius="medium" />}>
          <ConnectedPaymentsProfileDropdownWrapper />
        </Suspense>
        <Suspense fallback={null}>
          <TopLevelModals />
        </Suspense>
      </>
    );
  };

  return (
    <BladeProvider themeTokens={bladeTheme}>
      <I18nProvider>
        <ThemeProvider theme={theme}>
          <Provider store={store}>
            <ShellZustandToReduxSyncProvider store={store}>
              <SpiltzServiceProvider
                customLoader={() => <SkeletonLoader showOnlyMobileSearch={showOnlyMobileSearch} />}
                dashboardType="merchant"
              >
                <I18ServiceProvider>{getTopNavActionComponents()}</I18ServiceProvider>
              </SpiltzServiceProvider>
            </ShellZustandToReduxSyncProvider>
          </Provider>
        </ThemeProvider>
      </I18nProvider>
    </BladeProvider>
  );
}

export default TopNavActions;
