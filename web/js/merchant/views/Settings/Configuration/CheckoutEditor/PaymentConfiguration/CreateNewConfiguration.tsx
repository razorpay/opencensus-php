import React from 'react';
import { Box, Button, PlusIcon, Text } from '@razorpay/blade/components';

export function CreateNewConfiguration() {
  return (
    <Box
      display="flex"
      justifyContent="center"
      padding={['spacing.4', 'spacing.5']}
      alignItems="center"
      alignSelf="stretch"
      gap="spacing.4"
      borderRadius="large"
      borderStyle="dashed"
      borderColor="surface.border.gray.muted"
    >
      <Button variant="secondary" size="small" icon={PlusIcon} />
      <Box flexGrow="1">
        <Text size="medium" weight="medium">
          Create a custom payment configuration
        </Text>
      </Box>
    </Box>
  );
}
