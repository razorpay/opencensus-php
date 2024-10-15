import React from 'react';

import OnboardingDetailsProvider from './OnboardingDetailsContext';
import InvoiceStatsProvider from './InvoiceStatsContext';
import PopupProvider from './PopupContext';

const ContextWrapper = ({ children }) => (
  <InvoiceStatsProvider>
    <OnboardingDetailsProvider>
      <PopupProvider>{children}</PopupProvider>
    </OnboardingDetailsProvider>
  </InvoiceStatsProvider>
);

export default ContextWrapper;
