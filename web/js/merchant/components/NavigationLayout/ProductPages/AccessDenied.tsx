import React from 'react';
import { Box, Text, Heading, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

type AccessDeniedPageProps = {
  title: string;
  description: string;
};

const AccessDeniedPage: React.FC<AccessDeniedPageProps> = ({ title, description }) => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      padding="spacing.4"
      minHeight={{ m: '100%', l: '100%' }}
    >
      <Box
        maxHeight={'320px'}
        width={'320px'}
        height={'320px'}
        backgroundColor="surface.background.cloud.intense" // remove this later
        marginBottom={{ base: 'spacing.6', m: 'spacing.6', l: 'spacing.0' }}
      >
        <img
          src="https://cdn.razorpay.com/static/assets/connected-dashboard/product_access_denied.png"
          alt="Product Access denied image"
          style={{
            width: '100%',
            height: '100%',
            objectFit: 'cover',
          }}
        />
      </Box>

      <Box marginTop={{ base: 'spacing.4', m: 'spacing.10' }} textAlign="center">
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
