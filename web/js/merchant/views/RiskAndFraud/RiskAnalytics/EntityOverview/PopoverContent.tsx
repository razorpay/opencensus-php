import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { PopoverContentProps } from './types';
import { getComparisonData } from './utils';

const PopoverContent = ({ valueKey, actualValue, comparedValue }: PopoverContentProps) => {
  const label = getComparisonData(comparedValue, actualValue);

  return (
    <Box display="flex" flexDirection="column" paddingRight="spacing.5">
      <Text>
        Your {valueKey.replace(/_/g, ' ')} is {label}. Industries with similar cross-border payments
        volume and in the same category as yours are taken into account while calculating the
        average.
      </Text>
    </Box>
  );
};

export default PopoverContent;
