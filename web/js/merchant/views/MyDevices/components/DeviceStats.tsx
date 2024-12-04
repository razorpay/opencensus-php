import React from 'react';
import { Box, IconComponent, Text } from '@razorpay/blade/components';

const DeviceStats = ({ Icon, name, data }: { Icon: IconComponent; name: string; data: string }) => (
  <Box display={'flex'} flexDirection={'column'}>
    <Box marginBottom="spacing.5">
      <Icon size="large" />
    </Box>
    <Text color="surface.text.gray.subtle">{name}</Text>
    <Text size="large" color="surface.text.gray.normal" weight="semibold">
      {data}
    </Text>
  </Box>
);

export default DeviceStats;
