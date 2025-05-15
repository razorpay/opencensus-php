import { Box, Heading, Text } from '@razorpay/blade/components';
import React from 'react';

export const Header = () => {
  return (
    <Box display="flex" gap="spacing.3">
      <Box display="flex" flexDirection="column" gap="spacing.3">
        <Heading size="large">Buyer Protection</Heading>
        <Text size="small" color="surface.text.gray.muted">
          Buyer Protection boosts sales by building trust with 100% refunds on non-delivery or
          defective products. Set it up in just 5 minutes and give your customers confidence to buy!
        </Text>
      </Box>
    </Box>
  );
};
