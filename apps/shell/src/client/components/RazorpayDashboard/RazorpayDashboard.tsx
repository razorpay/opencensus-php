import React, { Suspense } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import ProductDashboard from '@federated/dashboards/payments/entry';
import ShellLayout from '../ShellLayout';

const RazorpayDashboard = () => (
  <ShellLayout>
    <Suspense
      fallback={
        <Box
          display="flex"
          alignItems="center"
          justifyContent="center"
          top="spacing.0"
          right="spacing.0"
          left="spacing.0"
          bottom="spacing.0"
          zIndex="1"
          height="100vh"
          position="absolute"
        >
          <Spinner label="Loading Dashboard..." accessibilityLabel="Dashboard Loader" />
        </Box>
      }
    >
      <ProductDashboard />
    </Suspense>
  </ShellLayout>
);

export default RazorpayDashboard;
