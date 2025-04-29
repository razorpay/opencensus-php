import React, { Suspense, lazy } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { Skeleton, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { matchPath, useLocation } from 'react-router-dom';

const UniversalSearch = lazy(() => import('merchant/components/HeaderNav/UniversalSearch'));

const ConnectedUniversalSearchWrapper: React.FC = () => {
  const user = useStore((state) => state.session.user);
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const location = useLocation();
  const currentPath = location.pathname;

  const isPartnersPathActive = Boolean(matchPath({ path: '/partners/*' }, currentPath));

  const isMobile = matchedDeviceType === 'mobile';

  const shouldShowSearch = user?.isUniversalSearchEnabled && !isMobile && !isPartnersPathActive;

  if (!shouldShowSearch) {
    return null;
  }

  return (
    <Suspense fallback={<Skeleton width="200px" height="32px" borderRadius="medium" />}>
      <UniversalSearch isConnectedNavigation={true} />
    </Suspense>
  );
};

export default ConnectedUniversalSearchWrapper;
