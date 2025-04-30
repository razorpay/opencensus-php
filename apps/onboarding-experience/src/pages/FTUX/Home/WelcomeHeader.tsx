import React from 'react';
import { Box, Divider, Heading, Text, ArrowRightIcon } from '@razorpay/blade/components';
import useMerchant from 'apps/onboarding-experience/src/common/hooks/useMerchant';

/**
 * Displays a personalized welcome message to the merchant at the top of the FTUX homepage.
 */
const WelcomeHeader = () => {
  const { data: merchantData } = useMerchant();
  const merchantName = merchantData?.merchantById?.name?.registered;

  return (
    <Box
      paddingY="spacing.7"
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      gap="spacing.7"
      alignSelf="stretch"
    >
      <Heading size="large">Welcome, {merchantName}</Heading>
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          m: 'row',
        }}
        alignItems="center"
        gap={{
          base: 'spacing.4',
          m: 'spacing.2',
        }}
      >
        <Text color="surface.text.gray.subtle" textAlign="center">
          You picked Website, so we’ll set it up first. You can always explore other products too{' '}
          <Text color="surface.text.gray.subtle" as="span" textDecorationLine="underline">
            here
          </Text>
        </Text>
        <ArrowRightIcon />
      </Box>
      <Divider width="100%" marginY="spacing.5" />
    </Box>
  );
};

export default WelcomeHeader;
