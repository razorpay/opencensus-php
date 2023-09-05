import React from 'react';
import {
  Badge,
  InfoIcon,
  Box,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';

import { StatusProps } from './types';

const Status = ({ variant, content, status }: StatusProps): JSX.Element => {
  return (
    <Box display="flex">
      <Badge
        marginLeft={{
          base: 'auto',
          l: 'spacing.0',
        }}
        variant={variant}
        icon={(props) => (
          <Tooltip content={content}>
            <TooltipInteractiveWrapper>
              <InfoIcon {...props} />
            </TooltipInteractiveWrapper>
          </Tooltip>
        )}
        size="large"
      >
        {titleCase(status)}
      </Badge>
    </Box>
  );
};

export default Status;
