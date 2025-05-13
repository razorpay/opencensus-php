import React, { Suspense, lazy } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { Box, Skeleton, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { matchPath, useLocation } from 'react-router-dom';
import { shouldDisplaySearchBasedOnDeviceType, isBillMeOnlyUser } from '@libs/shared-utils';

const UniversalSearch = lazy(() => import('merchant/components/HeaderNav/UniversalSearch'));

type ConnectedUniversalSearchWrapperProps = {
  showOnlyMobileSearch?: boolean;
};

const ConnectedUniversalSearchWrapper: React.FC<ConnectedUniversalSearchWrapperProps> = ({
  showOnlyMobileSearch,
}) => {
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

  const shouldShowSearch =
    user?.isUniversalSearchEnabled &&
    !isPartnersPathActive &&
    !isBillMeOnlyMerchant &&
    shouldDisplaySearchBasedOnDeviceType({
      isMobile,
      showOnlyMobileSearch,
    });

  if (!shouldShowSearch) {
    return null;
  }

  const getFallbackLoader = () => {
    if (showOnlyMobileSearch) {
      return (
        <Box display="flex" alignItems="center" gap="spacing.5" height="40px">
          <Skeleton width="100%" height="32px" borderRadius="medium" />
        </Box>
      );
    }
    return <Skeleton width="200px" height="32px" borderRadius="medium" />;
  };

  return (
    <Suspense fallback={getFallbackLoader()}>
      <UniversalSearch isConnectedNavigation={true} />
    </Suspense>
  );
};

export default ConnectedUniversalSearchWrapper;
