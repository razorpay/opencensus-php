import React, { ReactNode, Suspense } from 'react';
import { Button, Skeleton, TopNavActions, Box, useTheme } from '@razorpay/blade/components';
import { useGetActiveProduct } from '../hooks';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import { useBreakpoint } from '@razorpay/blade/utils';
import { shouldDisplaySearchBasedOnDeviceType } from '@libs/shared-utils';
import { loadRemote } from '@apps/shell/src/client/services/module-federation';

type NavActionsSkeletonLoaderProps = {
  showOnlyMobileSearch?: boolean;
};

const NavActionsSkeletonLoader = ({ showOnlyMobileSearch }: NavActionsSkeletonLoaderProps) => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const { isPartnersActive } = useGetActiveProduct();

  const shouldShowSearchSkeleton =
    !isPartnersActive &&
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
    <Box display="flex" alignItems="center" gap="spacing.5" height="40px">
      {shouldShowSearchSkeleton && <Skeleton width="200px" height="32px" borderRadius="medium" />}
      <Box display="flex" gap="spacing.4" marginLeft="auto">
        <Skeleton width="32px" height="32px" borderRadius="medium" />
        <Skeleton width="32px" height="32px" borderRadius="medium" />
        <Skeleton width="32px" height="32px" borderRadius="medium" />
      </Box>
    </Box>
  );
};

export const withTopNavActions = (WrappedComponent: React.ComponentType<any>) => {
  return (props: any) => {
    const { theme } = useTheme();
    const { matchedDeviceType } = useBreakpoint({
      breakpoints: theme.breakpoints,
    });
    const isMobile = matchedDeviceType === 'mobile';
    const { showOnlyMobileSearch } = props;

    if (showOnlyMobileSearch) {
      return (
        <Box width="100%" zIndex="100" paddingX="spacing.2" paddingTop="spacing.1">
          <Suspense
            fallback={<NavActionsSkeletonLoader showOnlyMobileSearch={showOnlyMobileSearch} />}
          >
            <WrappedComponent {...props} />
          </Suspense>
        </Box>
      );
    }

    if (isMobile) {
      return (
        <Box display="flex" alignItems="center" gap="spacing.3">
          <Suspense fallback={<NavActionsSkeletonLoader />}>
            <WrappedComponent {...props} />
          </Suspense>
        </Box>
      );
    }

    return (
      <TopNavActions>
        <Suspense fallback={<NavActionsSkeletonLoader />}>
          <WrappedComponent {...props} />
        </Suspense>
      </TopNavActions>
    );
  };
};

const BankingActions = React.lazy(() => loadRemote('@federated/cross-repo/x/BankingTopNavItems'));
const PaymentsAndPartnersActions = React.lazy(
  () => import('@federated/dashboards/payments/connected-navigation/PaymentsTopNavActions'),
);

const HomeActions = React.lazy(
  () => import('@federated/apps/one-home/TopNavActions/OneHomeTopNavActions'),
);

const PaymentsTopNavActions = withTopNavActions(PaymentsAndPartnersActions);
const PartnersTopNavActions = withTopNavActions(PaymentsAndPartnersActions);
const BankingTopNavActions = withTopNavActions(BankingActions);
const HomeTopNavActions = withTopNavActions(HomeActions);

type HeaderActionsLoaderProps = {
  showOnlyMobileSearch?: boolean;
};

export const HeaderActionsLoader = (props: HeaderActionsLoaderProps): JSX.Element | null => {
  const { isPaymentsActive, isBankingActive, isPartnersActive, isHomeActive } =
    useGetActiveProduct();
  const { products } = useConnectedNavigationStore();

  const actionType = products?.selectedProduct?.selectAction?.actionType;
  const excludedActionTypes = ['growth_page', 'access_denied_page'];
  const shouldHideHeaderActions = excludedActionTypes.includes(actionType);

  if (!actionType) {
    return <NavActionsSkeletonLoader />;
  }

  if (shouldHideHeaderActions) {
    return null;
  }

  switch (true) {
    case isPaymentsActive:
      return <PaymentsTopNavActions {...props} />;
    case isBankingActive:
      return <BankingTopNavActions {...props} />;
    case isPartnersActive:
      return <PartnersTopNavActions {...props} />;
    case isHomeActive:
      return <HomeTopNavActions {...props} />;
    default:
      return null;
  }
};
