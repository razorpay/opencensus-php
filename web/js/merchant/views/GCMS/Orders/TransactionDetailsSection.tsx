import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

const TransactionDetailsSection = ({ heading, value }) => {
  return (
    <Box
      display={'flex'}
      flexDirection={'column'}
      justifyContent={'space-between'}
      marginRight={'spacing.4'}
      borderRightWidth={'thick'}
      borderRightColor={'surface.border.gray.subtle'}
      paddingRight={'spacing.6'}
      flex={1}
    >
      <Text size="small" color="surface.text.gray.muted">
        {heading}
      </Text>
      <Text weight="semibold">{value}</Text>
    </Box>
  );
};

export default TransactionDetailsSection;
