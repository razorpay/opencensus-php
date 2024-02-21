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
        <Text weight="bold">Delivery Breakup</Text>
      </Box>
      <Box display="flex" flexDirection="row">
        <Box width="80px">
          <Text color="surface.text.subdued.lowContrast">Uploaded</Text>
          <Box paddingTop="spacing.1">
            <Text weight="bold">{uploaded}</Text>
          </Box>
        </Box>
        <Box width="80px">
          <Text color="surface.text.subdued.lowContrast">Delivered</Text>
          <Box paddingTop="spacing.1">
            <Text color="feedback.positive.action.text.link.active.lowContrast" weight="bold">
              {delivered}
            </Text>
          </Box>
        </Box>
        <Box width="80px">
          <Text color="surface.text.subdued.lowContrast">Failed</Text>
          <Box paddingTop="spacing.1">
            <Text color="feedback.negative.action.text.primary.default.lowContrast" weight="bold">
              {failed}
            </Text>
          </Box>
        </Box>
      </Box>
    </OrderDetailsDeliverySectionDistributionContainer>
  );
};

export default OrderDetailsDeliveryBreakup;
