import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { LogoSvg } from './styled';

export const Loader = () => (
  <Box
    display="flex"
    flexDirection="column"
    gap="spacing.4"
    position="fixed"
    height="100vh"
    justifyContent="center"
    alignItems="center"
    width="100%"
  >
    <LogoSvg
      width="48"
      height="48"
      viewBox="0 0 48 48"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <rect width="48" height="48" rx="8" />
      <path d="M22.1725 17.3261L20.7541 22.4642L28.8729 17.2968L23.563 36.7946L28.9558 36.799L36.8 8" />
      <path d="M13.4335 28.6029L11.2 36.7997H22.253L26.7758 20.125L13.4335 28.6029Z" />
    </LogoSvg>
    <Text>Loading...</Text>
  </Box>
);
