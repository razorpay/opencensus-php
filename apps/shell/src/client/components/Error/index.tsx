import React from 'react';
import { Box, Heading, Text, Button } from '@razorpay/blade/components';
import { Illustration } from './Illustration';

export const Error = () => (
  <Box
    display="flex"
    flexDirection="column"
    position="fixed"
    height="80vh"
    justifyContent="center"
    alignItems="center"
    width="100%"
    gap="spacing.5"
  >
    <Illustration />
    <Heading size="xlarge">An unexpected error has occured</Heading>
    <Text color="surface.text.gray.subtle">
      We are working on fixing it. Please try reloading the page.
    </Text>
    <Button onClick={() => window.location.reload()}>Reload</Button>
  </Box>
);
