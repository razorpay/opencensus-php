import React from 'react';
import { Box, CheckIcon, CloseIcon } from '@razorpay/blade/components';

import { IconBackground } from 'merchant/views/Optimizer/AddProvider/components/styled';

export const CoverageIcon = ({ status }) => {
  const iconColor =
    status === 'positive'
      ? 'feedback.background.positive.intense'
      : 'feedback.background.negative.intense';
  const IconComp = status === 'positive' ? CheckIcon : CloseIcon;
  return (
    <IconBackground status={status}>
      <Box display="flex" alignItems="center" justifyContent="center" paddingLeft="spacing.1">
        <IconComp color={iconColor} size="medium" />
      </Box>
    </IconBackground>
  );
};
