import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { SummaryItemWrapper } from './styles';

const SummaryTable = ({ shippingConfig }): JSX.Element => {
  return (
    <SummaryItemWrapper>
      <Box display="flex" flexDirection="column" gap="spacing.4">
        <Text weight="semibold">{shippingConfig.zone.name}</Text>
        <Text color="surface.text.gray.muted">Delivery in {shippingConfig.delivery_in}</Text>
      </Box>
    </SummaryItemWrapper>
  );
};

export default SummaryTable;
