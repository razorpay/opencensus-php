import React from 'react';

import { Box, CheckCircleIcon, Switch, Text } from '@razorpay/blade/components';

type LineItemsProps = {
  title?: string | React.ReactNode;
  subTitle?: string | React.ReactNode;
  rightChildren?: React.ReactNode;
  extraItems?: React.ReactNode;
  isMobile?: boolean;
  isDetailsFilled?: boolean;
  isMandatoryInfo?: boolean;
};

const LineItems: React.FC<LineItemsProps> = ({
  title,
  subTitle,
  rightChildren,
  extraItems,
  isMobile,
  isDetailsFilled,
  isMandatoryInfo = false,
}) => {
  return (
    <Box
      display="flex"
      padding="spacing.7"
      flexDirection="column"
      justifyContent="space-between"
      gap="spacing.3"
      borderRadius="small"
      borderWidth="thin"
      borderStyle="solid"
      borderColor="surface.border.gray.muted"
      marginBottom={isMobile ? 'spacing.8' : 'spacing.0'}
    >
      <Box display="flex" alignItems="center" justifyContent="space-between" gap="spacing.3">
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          justifyContent="space-between"
        >
          <Box display="flex" alignItems="center" gap="spacing.2">
            {isDetailsFilled ? (
              <CheckCircleIcon color="feedback.icon.positive.intense" size="medium" />
            ) : null}
            <Box display="flex" alignItems="center" gap="spacing.0">
              <Text weight="semibold" color="surface.text.gray.normal" variant="body" size="large">
                {title}
              </Text>
              {isMandatoryInfo ? (
                <Text
                  weight="semibold"
                  color="interactive.text.notice.normal"
                  variant="body"
                  size="large"
                >
                  *
                </Text>
              ) : null}
            </Box>
          </Box>
          {!isDetailsFilled ? subTitle : null}
        </Box>
        {rightChildren}
      </Box>
      {extraItems}
    </Box>
  );
};

export default LineItems;
