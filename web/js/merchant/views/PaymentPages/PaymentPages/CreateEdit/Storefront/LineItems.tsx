import React from 'react';

import { Box, Text } from '@razorpay/blade/components';

type LineItemsProps = {
  title?: string | React.ReactNode;
  subTitle?: string | React.ReactNode;
  rightChildren?: React.ReactNode;
  extraItems?: React.ReactNode;
};

const LineItems: React.FC<LineItemsProps> = ({ title, subTitle, rightChildren, extraItems }) => {
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
    >
      <Box display="flex" alignItems="center" justifyContent="space-between" gap="spacing.3">
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          justifyContent="space-between"
        >
          <Text weight="semibold" color="surface.text.gray.normal" variant="body" size="large">
            {title}
          </Text>
          <Text color="interactive.text.notice.normal" variant="body" size="small" weight="regular">
            {subTitle}
          </Text>
        </Box>
        {rightChildren}
      </Box>
    </Box>
  );
};

export default LineItems;
