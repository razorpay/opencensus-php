import React from 'react';
import { Box } from '@razorpay/blade/components';

import FullPageViewWrapper from 'merchant/views/onboarding/FullPageViewWrapper';

import BusinessWebsiteDetails from './BusinessWebsiteDetails';

const OnboardingBusinessWebsiteDetails = (): JSX.Element => (
  <FullPageViewWrapper>
    <Box paddingX="spacing.6" paddingY="spacing.8">
      <BusinessWebsiteDetails />
    </Box>
  </FullPageViewWrapper>
);

export default OnboardingBusinessWebsiteDetails;
