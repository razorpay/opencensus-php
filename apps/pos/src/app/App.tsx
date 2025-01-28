import React, { useLayoutEffect, useEffect } from 'react';
import { useLocation, useRoutes } from 'react-router-dom';
import { Box, useToast } from '@razorpay/blade/components';
import { PARENT_ROUTE_CONFIG } from './routes';

const AssistedOnboarding: React.FC<{}> = () => {
  const location = useLocation();
  const routes = useRoutes(PARENT_ROUTE_CONFIG);
  const toast = useToast();

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
    <Box
      display="flex"
      flexDirection="column"
      backgroundColor="surface.background.gray.moderate"
      minHeight="94vh"
      paddingTop="spacing.4"
    >
      {routes}
    </Box>
  );
};

export default AssistedOnboarding;
