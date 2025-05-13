import React from 'react';
import { Box, Spinner, Heading } from '@razorpay/blade/components';

export const LoadingContent: React.FC = () => (
  <>
    <Spinner size="xlarge" color="primary" accessibilityLabel="Setting up SSO" />
    <Box display="flex" flexDirection="column" alignItems="center">
      <Heading>Setting up Razorpay Login for your store</Heading>
    </Box>
  </>
);
