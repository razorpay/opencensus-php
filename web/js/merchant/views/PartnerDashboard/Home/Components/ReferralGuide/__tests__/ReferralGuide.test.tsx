import React from 'react';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { BrowserRouter as Router } from 'react-router-dom';
import { ThemeProvider } from 'styled-components';

import ReferralGuide from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/index';
import { orgDetails } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';

const setUpComponent = (TestElement: JSX.Element) => {
  return (
    <Router>
      <ThemeProvider theme={theme}>{TestElement}</ThemeProvider>
    </Router>
  );
};
describe('<ReferralGuide /> ', () => {
  test('Added First Submerchant', () => {
    const props = {
      partnerName: 'John',
      isFirstReferralDone: true,
      isFetching: false,
      openModal: () => {},
      closeModal: () => {},
      handleReferClient: () => {},
      handleAggregatorApplyNow: () => {},
      isUserOwner: true,
      user: { isOnboardAsResellers: true },
      org: orgDetails,
    };
    render(setUpComponent(<ReferralGuide {...props} />));
    expect(screen.getByText(`Good Job ${props.partnerName}!! Keep Referring`)).toBeInTheDocument();
  });
});
