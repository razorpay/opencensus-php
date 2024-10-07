import React from 'react';
import {
  Box,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  UserIcon,
  MapPinIcon,
  AlertTriangleIcon,
} from '@razorpay/blade/components';

import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';

const InfoWithTooltip = ({ content, fallbackText, isSubtext }) => {
  if (content) {
    return (
      <TooltipWrapper>
        <Tooltip content={content}>
          <TooltipInteractiveWrapper>
            <Box display="flex" alignItems="center" overflow="hidden" whiteSpace="pre-wrap">
              <Text
                size={isSubtext ? 'small' : 'medium'}
                color={isSubtext ? 'surface.text.gray.muted' : 'surface.text.gray.subtle'}
                truncateAfterLines={1}
              >
                {content}
              </Text>
            </Box>
          </TooltipInteractiveWrapper>
        </Tooltip>
      </TooltipWrapper>
    );
  }

  return (
    <Box display="flex" alignItems="center">
      <Text size="medium" color="interactive.text.negative.normal">
        {fallbackText}
      </Text>
    </Box>
  );
};

const SenderDetails = ({ name, country }) => {
  if (!name && !country)
    return (
      <Box display="flex" flexDirection="row" alignItems="center">
        <AlertTriangleIcon marginRight="spacing.3" color="interactive.icon.negative.normal" />
        <Text color="interactive.text.negative.normal">Unavailable</Text>
      </Box>
    );

  return (
    <Box width="160px">
      <Box>
        <InfoWithTooltip content={name} icon={UserIcon} fallbackText="Name not available" />
      </Box>
      <Box>
        <InfoWithTooltip
          isSubtext
          content={country}
          icon={MapPinIcon}
          fallbackText="Country not available"
        />
      </Box>
    </Box>
  );
};

export default SenderDetails;
