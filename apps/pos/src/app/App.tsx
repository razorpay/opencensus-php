import React, { useLayoutEffect } from 'react';
import { useLocation, useRoutes } from 'react-router-dom';
import { Box, ToastContainer } from '@razorpay/blade/components';
import { PARENT_ROUTE_CONFIG } from './routes';

const AssistedOnboarding: React.FC<{}> = () => {
  const location = useLocation();
  const routes = useRoutes(PARENT_ROUTE_CONFIG);

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
      <ToastContainer />
    </Box>
  );
};

export default AssistedOnboarding;
