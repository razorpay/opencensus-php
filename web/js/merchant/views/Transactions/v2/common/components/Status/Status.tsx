import React from 'react';
import {
  Badge,
  InfoIcon,
  Box,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';

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
          <TooltipWrapper
            onClick={(e) => {
              e.stopPropagation();
            }}
          >
            <Tooltip content={content}>
              <TooltipInteractiveWrapper>
                <InfoIcon {...props} />
              </TooltipInteractiveWrapper>
            </Tooltip>
          </TooltipWrapper>
        )}
        size="large"
      >
        {titleCase(status)}
      </Badge>
    </Box>
  );
};

export default Status;
