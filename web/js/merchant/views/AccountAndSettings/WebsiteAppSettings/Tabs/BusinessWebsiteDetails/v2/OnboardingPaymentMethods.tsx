import React from 'react';

import PaymentMethods from 'merchant/views/AccountAndSettings/PaymentMethods';
import FullPageViewWrapper from 'merchant/views/onboarding/FullPageViewWrapper';

const OnboardingPaymentMethods = (): JSX.Element => (
  <FullPageViewWrapper>
    <PaymentMethods isFullScreenView routePrefix="/onboarding" />
  </FullPageViewWrapper>
);

export default OnboardingPaymentMethods;
