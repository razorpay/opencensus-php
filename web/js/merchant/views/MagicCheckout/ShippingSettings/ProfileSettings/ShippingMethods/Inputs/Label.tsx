import {
  Box,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';
import React from 'react';

const Label = ({
  value,
  error,
  required = true,
  showTooltip,
  tooltipContent,
}: {
  value: string;
  error?: string;
  required?: boolean;
  showTooltip?: boolean | undefined;
  tooltipContent?: string | undefined;
}): JSX.Element => {
  const hasError = error && error?.length > 0;
  return (
    <Box width="120px">
      <Box display="flex">
        <Text
          color={
            hasError ? 'feedback.text.negative.lowContrast' : 'feedback.text.neutral.lowContrast'
          }
          weight="bold"
        >
          {value}
          {required ? (
            <Text as="span" color="feedback.text.negative.lowContrast">
              *
            </Text>
          ) : null}
        </Text>
        {showTooltip ? (
          <Tooltip content={tooltipContent || ''} placement="bottom">
            <TooltipInteractiveWrapper>
              <InfoIcon
                color="surface.text.muted.lowContrast"
                marginLeft="spacing.2"
                position="relative"
                top="spacing.1"
                size="medium"
              />
            </TooltipInteractiveWrapper>
          </Tooltip>
        ) : null}
      </Box>
      {hasError ? (
        <Text size="small" color="feedback.text.negative.lowContrast">
          {error}
        </Text>
      ) : null}
    </Box>
  );
};

export default Label;
