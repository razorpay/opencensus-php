import React from 'react';
import { Box, Heading } from '@razorpay/blade/components';

/**
 * Displays a personalized welcome message to the merchant at the top of the FTUX homepage.
 */
const WelcomeHeader = () => {
  return (
    <Box>
      <Heading size="large">Welcome, John!</Heading>
    </Box>
  );
};

export default WelcomeHeader;
