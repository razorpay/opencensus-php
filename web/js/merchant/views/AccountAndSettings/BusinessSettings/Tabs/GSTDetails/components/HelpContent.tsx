import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

const HelpContent = (): JSX.Element => (
  <Box>
    <Text weight="bold" size="small">
      Want to add a different GST?
    </Text>
    <Text size="small">
      GSTIN linked to your PAN are shown above. To add a different GST not linked to your PAN,
      create a new Razorpay Account.
    </Text>
  </Box>
);

export default HelpContent;
