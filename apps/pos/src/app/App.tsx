import React, { useLayoutEffect, useEffect, useState, useRef, useCallback } from 'react';
import { useLocation, useRoutes } from 'react-router-dom';
import { Box, useToast } from '@razorpay/blade/components';
import { PARENT_ROUTE_CONFIG } from './routes';
import RzpBrandLogo from 'apps/pos/src/assets/RzpBrandLogo.svg';
import { AnimatedBrandLogo } from 'apps/pos/src/app/styled';
import { usePullToRefresh } from 'apps/pos/src/app/hooks/usePullToRefresh';


const AssistedOnboarding: React.FC = () => {
  const location = useLocation();
  const routes = useRoutes(PARENT_ROUTE_CONFIG);
  const toast = useToast();
  const containerRef = useRef<HTMLDivElement>(null);
  const pullToRefreshRef = useRef<HTMLDivElement>(null);
  const { setupPullToRefresh } = usePullToRefresh();
  
  useEffect(() => {
    const cleanup = setupPullToRefresh(containerRef.current, pullToRefreshRef.current);
    return cleanup;
  }, [setupPullToRefresh]);
  
  useEffect(() => {
    const pullRefreshContainer = pullToRefreshRef.current;
    if (pullRefreshContainer) {
      pullRefreshContainer.style.transition = 'all 200ms ease';
    }
  }, []);

  useEffect(() => {
    // This is a temp fix for toast not showing up on initial trigger
    toast.show({
      content: 'Loading...',
      color: 'information',
      autoDismiss: true,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useLayoutEffect(() => {
    window.scrollTo(0, 0);
  }, [location.pathname]);

  return (
    <>
      <Box
        ref={pullToRefreshRef}
        display={'flex'}
        justifyContent={'center'}
        alignItems={'center'}
        height={'200px'}
        position={'fixed'}
        top={'-200px'}
        zIndex={100}
        backgroundColor="surface.background.gray.moderate"
        width={'100%'}
      >
        <AnimatedBrandLogo src={RzpBrandLogo}/>
      </Box>
      <Box
        ref={containerRef}
        display="flex"
        flexDirection="column"
        backgroundColor="surface.background.gray.moderate"
        minHeight="94vh"
        paddingTop={'spacing.3'}
      >
        {routes}
      </Box>
    </>
  );
};

export default AssistedOnboarding;
