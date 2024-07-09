import React, { useMemo } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { Box, ToastContainer } from '@razorpay/blade/components';
import { getParentRouteConfig } from './routes';

const AssistedOnboarding: React.FC<{}> = () => {
  const routeConfig = useMemo(() => getParentRouteConfig(), []);
  return (
    <Box backgroundColor="surface.background.gray.moderate" minHeight="90vh" paddingTop="spacing.4">
      <Routes>
        {routeConfig.child.map((route) => (
          <Route key={route.route} path={route.route} element={route.view} />
        ))}
        <Route path="*" element={<Navigate to={routeConfig.fallback} replace />} />
      </Routes>
      <ToastContainer />
    </Box>
  );
};

export default AssistedOnboarding;
