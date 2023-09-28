import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { SummaryItemWrapper } from './styles';

const SummaryTable = ({ shippingConfig }): JSX.Element => {
  return (
    <SummaryItemWrapper>
      <Box display="flex" flexDirection="column" gap="spacing.4">
        <Text weight="bold">{shippingConfig.zone.name}</Text>
        <Text type="subdued">Delivery in {shippingConfig.delivery_in}</Text>
      </Box>
    </SummaryItemWrapper>
  );
};

export default SummaryTable;
