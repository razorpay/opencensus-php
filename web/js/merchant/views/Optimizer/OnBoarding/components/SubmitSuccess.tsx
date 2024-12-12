import React from 'react';
import { Box, Text, Heading, Divider, Button } from '@razorpay/blade/components';
import { trackOptimizerEvents } from 'merchant/views/Optimizer/track';
import { OPTI_BLOG, ONBOARDING_SUCCESS } from 'merchant/views/Optimizer/OnBoarding/constants';
import { OPTIMIZER_BLOG_CLICK } from 'merchant/views/Optimizer/OnBoarding/track';

export const SubmitSuccess = (): JSX.Element => {
  const goToBlogClicked = () => {
    window.open(OPTI_BLOG, '_blank');
    trackOptimizerEvents(OPTIMIZER_BLOG_CLICK);
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.8"
      justifyContent="center"
      padding="spacing.8"
      backgroundColor="surface.background.sea.subtle"
      borderRadius="large"
      flex="1"
    >
      <Box display="flex" flexDirection="column" gap="spacing.8" justifyContent="center" flex="1">
        <Box>
          <img src={ONBOARDING_SUCCESS} alt="success" />
        </Box>
        <Text size="small" color="surface.text.gray.muted">
          Optimizer - Razorpay's AI powered payments router
        </Text>
        <Box display="flex" flexDirection="column" gap="spacing.4">
          <Heading size="large" color="surface.text.gray.subtle">
            Request submitted successfully!
          </Heading>
          <Text size="small" color="surface.text.gray.muted">
            We sent you an email confirming the same. Sit back and relax, we'll get back to you in
            48hrs.
          </Text>
          <Divider />
        </Box>
        <Button isFullWidth={true} onClick={goToBlogClicked}>
          Go to Optimizer blog
        </Button>
      </Box>
    </Box>
  );
};
