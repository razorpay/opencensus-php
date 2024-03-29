import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { OrderDetailsDeliverySectionDistributionContainer } from './styled';

const OrderDetailsDeliveryBreakup = ({
  uploaded,
  delivered,
  failed,
}: {
  uploaded: number;
  delivered: number;
  failed: number;
}) => {
  return (
    <OrderDetailsDeliverySectionDistributionContainer>
      <Box>
        <Text weight="semibold">Delivery Breakup</Text>
      </Box>
      <Box display="flex" flexDirection="row">
        <Box width="80px">
          <Text color="surface.text.gray.muted">Uploaded</Text>
          <Box paddingTop="spacing.1">
            <Text weight="semibold">{uploaded}</Text>
          </Box>
        </Box>
        <Box width="80px">
          <Text color="surface.text.gray.muted">Delivered</Text>
          <Box paddingTop="spacing.1">
            <Text color="interactive.text.positive.subtle" weight="semibold">
              {delivered}
            </Text>
          </Box>
        </Box>
        <Box width="80px">
          <Text color="surface.text.gray.muted">Failed</Text>
          <Box paddingTop="spacing.1">
            <Text color="interactive.text.negative.subtle" weight="semibold">
              {failed}
            </Text>
          </Box>
        </Box>
      </Box>
    </OrderDetailsDeliverySectionDistributionContainer>
  );
};

export default OrderDetailsDeliveryBreakup;
