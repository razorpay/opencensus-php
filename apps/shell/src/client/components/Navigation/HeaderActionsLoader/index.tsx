import React, { ReactNode, Suspense } from 'react';
import { Button, Skeleton, TopNavActions, Box, useTheme } from '@razorpay/blade/components';
import { useGetActiveProduct } from '../hooks';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import { useBreakpoint } from '@razorpay/blade/utils';

const NavActionsSkeletonLoader = () => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const { isPartnersActive } = useGetActiveProduct();

  const shouldShowSearchSkeleton = !isMobile && !isPartnersActive;

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

const PaymentsAndPartnersActions = React.lazy(
  () => import('@federated/dashboards/payments/connected-navigation/PaymentsTopNavActions'),
);

const HomeActions = React.lazy(
  () => import('@federated/apps/one-home/TopNavActions/OneHomeTopNavActions'),
);

const BankingActions = () => null;

const PaymentsTopNavActions = withTopNavActions(PaymentsAndPartnersActions);
const PartnersTopNavActions = withTopNavActions(PaymentsAndPartnersActions);
const BankingTopNavActions = withTopNavActions(BankingActions);
const HomeTopNavActions = withTopNavActions(HomeActions);

export const HeaderActionsLoader = (): JSX.Element | null => {
  const { isPaymentsActive, isBankingActive, isPartnersActive, isHomeActive } =
    useGetActiveProduct();
  const { products } = useConnectedNavigationStore();

  const actionType = products?.selectedProduct?.selectAction?.actionType;
  const excludedActionTypes = ['growth_page', 'access_denied_page'];
  const shouldHideHeaderActions = excludedActionTypes.includes(actionType);

  if (shouldHideHeaderActions) {
    return null;
  }

  switch (true) {
    case isPaymentsActive:
      return <PaymentsTopNavActions />;
    case isBankingActive:
      return <BankingTopNavActions />;
    case isPartnersActive:
      return <PartnersTopNavActions />;
    case isHomeActive:
      return <HomeTopNavActions />;
    default:
      return null;
  }
};
