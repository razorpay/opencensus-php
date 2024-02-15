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

const IconComponent = ({ icon: BladeIcon }) => {
  if (!BladeIcon) return null;

  return (
    <Box
      width="14px"
      height="14px"
      display="flex"
      alignItems="center"
      justifyContent="center"
      marginRight="spacing.1"
    >
      <BladeIcon size="small" />
    </Box>
  );
};

const InfoWithTooltip = ({ content, icon, fallbackText }) => {
  if (content) {
    return (
      <TooltipWrapper>
        <Tooltip content={content}>
          <TooltipInteractiveWrapper>
            <Box display="flex" alignItems="center" overflow="hidden" whiteSpace="pre-wrap">
              <IconComponent icon={icon} />
              <Text truncateAfterLines={1}>{content}</Text>
            </Box>
          </TooltipInteractiveWrapper>
        </Tooltip>
      </TooltipWrapper>
    );
  }

  return (
    <Box display="flex" alignItems="center">
      <IconComponent icon={icon} />
      <Text size="medium" color="feedback.negative.action.text.link.default.lowContrast">
        {fallbackText}
      </Text>
    </Box>
  );
};

const SenderDetails = ({ name, country }) => {
  return (
    <Box width="160px">
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
