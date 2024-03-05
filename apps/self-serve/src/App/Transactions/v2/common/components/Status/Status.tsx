import React from 'react';
import {
  Badge,
  InfoIcon,
  Box,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import { titleCase } from '@dashboard/shared-utils/rzp-utils';

import { StatusProps } from './types';
import { TooltipWrapper } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/styled';

const Status = ({ variant, content, status }: StatusProps): JSX.Element => {
  return (
    <Box display="flex">
      <Badge
        marginLeft={{
          base: 'auto',
          l: 'spacing.0',
        }}
        variant={variant}
        icon={(props): JSX.Element => (
          <TooltipWrapper
            onClick={(e: { stopPropagation: () => void }) => {
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
