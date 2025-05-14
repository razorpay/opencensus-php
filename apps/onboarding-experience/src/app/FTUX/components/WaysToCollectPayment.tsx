import React from 'react';
import { Box, Heading, Divider, AvatarGroup, Avatar } from '@razorpay/blade/components';

const WaysToCollectPayment = () => {
  return (
    <Box width="100%" display="flex" flexDirection="row" alignItems="center" marginY="spacing.4">
      <Divider />
      <Box paddingX="spacing.5" display="flex" alignItems="center" gap="spacing.4">
        <AvatarGroup>
          <Avatar color="neutral" name="B" />
          <Avatar color="neutral" name="A" />
        </AvatarGroup>
        <Heading color="surface.text.gray.normal" weight="semibold" size="large" textAlign="center">
          More ways to accept payments
        </Heading>
        <AvatarGroup>
          <Avatar color="neutral" name="B" />
          <Avatar color="neutral" name="A" />
        </AvatarGroup>
      </Box>
      <Divider />
    </Box>
  );
};

export default WaysToCollectPayment;
