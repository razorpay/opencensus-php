import React from 'react';
import { Box } from '@razorpay/blade/components';

import FullPageViewWrapper from 'merchant/views/onboarding/FullPageViewWrapper';

import BusinessWebsiteDetailsWrapper from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/Wrapper';

const OnboardingBusinessWebsiteDetails = (): JSX.Element => (
  <FullPageViewWrapper>
    <Box paddingX="spacing.6" paddingY="spacing.8">
      <BusinessWebsiteDetailsWrapper />
    </Box>
  </FullPageViewWrapper>
);

export default OnboardingBusinessWebsiteDetails;
