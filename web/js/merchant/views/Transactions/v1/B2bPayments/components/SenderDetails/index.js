import React from 'react';
import {
  Box,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  UserIcon,
  MapPinIcon,
} from '@razorpay/blade/components';

import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';

const InfoWithTooltip = ({ content, icon: IconComponent, fallbackText }) => {
  if (content) {
    return (
      <TooltipWrapper>
        <Tooltip content={content}>
          <TooltipInteractiveWrapper>
            <Box display="flex" alignItems="center">
              {IconComponent && <IconComponent size="small" marginRight="spacing.1" />}
              <Text truncateAfterLines={1}>{content}</Text>
            </Box>
          </TooltipInteractiveWrapper>
        </Tooltip>
      </TooltipWrapper>
    );
  }

  return (
    <Box display="flex" alignItems="center">
      {IconComponent && <IconComponent size="small" marginRight="spacing.1" />}
      <Text size="medium" color="feedback.negative.action.text.link.default.lowContrast">
        {fallbackText}
      </Text>
    </Box>
  );
};

const SenderDetails = ({ name, country }) => {
  return (
    <Box maxWidth="160px">
      <Box>
        <InfoWithTooltip content={name} icon={UserIcon} fallbackText="Name not available" />
      </Box>
      <Box>
        <InfoWithTooltip content={country} icon={MapPinIcon} fallbackText="Country not available" />
      </Box>
    </Box>
  );
};

export default SenderDetails;
