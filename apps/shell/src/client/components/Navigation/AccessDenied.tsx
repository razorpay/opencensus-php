import React from 'react';
import { Box, Text, Heading, useTheme, LockIcon } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';

const AccessDeniedPage = () => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  const { products } = useConnectedNavigationStore();

  const selectedProduct = (products as any)?.selectedProduct;
  const data = selectedProduct?.selectAction?.pageData || {};
  const title = data?.title;
  const description = data?.description;

  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      padding="spacing.4"
      minHeight={{ base: '100%', m: '100%', l: '100%' }}
    >
      <LockIcon size="2xlarge" color="surface.icon.primary.normal" />

      <Box marginTop={{ base: 'spacing.8', m: 'spacing.7' }} textAlign="center">
        <Heading
          as="h1"
          size={'2xlarge'}
          weight="semibold"
          marginBottom="spacing.2"
          color={'surface.text.gray.normal'}
        >
          {title}
        </Heading>
        <Text
          variant="body"
          weight={'medium'}
          size={isMobile ? 'large' : 'medium'}
          marginBottom="spacing.4"
          color={'surface.text.gray.muted'}
        >
          {description}
        </Text>
      </Box>
    </Box>
  );
};

export default AccessDeniedPage;
