import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import EmptyTableIllustration from 'apps/pos/src/assets/emptyTable.svg';

const EmptyScreen = (): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      alignItems="center"
      padding="spacing.5"
      marginTop="80px"
    >
      <img src={EmptyTableIllustration} alt="empty table" height="107px" />
      <Heading color="surface.text.gray.subtle" marginTop="spacing.4">
        No data available
      </Heading>
      <Text textAlign="center" color="surface.text.gray.muted">
        We couldn't find any merchant details associated with your requests
      </Text>
    </Box>
  );
};

export default EmptyScreen;
