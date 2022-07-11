import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { ReferralGuide } from '../index';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { BrowserRouter as Router } from 'react-router-dom';

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
    };
    render(setUpComponent(<ReferralGuide {...props} />));
    expect(screen.getByText(`Good Job ${props.partnerName}!! Keep Referring`)).toBeInTheDocument();
  });
});
