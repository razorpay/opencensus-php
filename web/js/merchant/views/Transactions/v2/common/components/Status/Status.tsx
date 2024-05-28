import React from 'react';
import {
  Badge,
  InfoIcon,
  Box,
  Tooltip,
  TooltipInteractiveWrapper,
  CloseIcon,
} from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { PaymentStatus } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

import { StatusProps } from './types';

const Status = ({ variant, content, status, isFailedIconEnabled }: StatusProps): JSX.Element => {
  return (
    <Box display="flex">
      <Badge
        marginLeft={{
          base: 'auto',
          l: 'spacing.0',
        }}
        color={variant}
        icon={(props) =>
          isFailedIconEnabled && status === PaymentStatus.FAILED ? (
            <CloseIcon {...props} />
          ) : (
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
          )
        }
        size="large"
      >
        {titleCase(status)}
      </Badge>
    </Box>
  );
};

export default Status;
