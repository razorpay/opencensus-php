import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import type { IconComponent } from '@razorpay/blade/components';

const ExploreItem = ({
  icon: Icon,
  title,
  description,
}: {
  icon: IconComponent;
  title?: string;
  description: string;
}): React.ReactElement => {
  return (
    <Box display="flex" gap="spacing.4" maxWidth="300px">
      <Box
        borderRadius="medium"
        padding="spacing.5"
        backgroundColor="surface.background.gray.subtle"
        display="flex"
        alignSelf="start"
      >
        <Icon color="interactive.icon.neutral.subtle" size="medium" />
      </Box>
      <Box>
        <Text color="surface.text.gray.subtle" size="medium" weight="semibold">
          {title}
        </Text>
        <Text size="small" color="surface.text.gray.muted">
          {description}
        </Text>
      </Box>
    </Box>
  );
};

export default ExploreItem;
