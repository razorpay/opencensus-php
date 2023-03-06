import React from 'react';

import PartnerTypeSelection from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/PartnerTypeSelection';
import { render, screen, userEvent } from 'test-utils';
// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  setStep: () => {},
  showNotification: () => {},
};

jest.mock('newAuth/signup/components/PartnerSignup/components/api', () => ({
  __esModule: true,
  ...jest.requireActual('newAuth/signup/components/PartnerSignup/components/api'),
  updatePartnerTypeAndConsent: () => new Promise((resolve) => resolve()),
}));

describe('PartnerTypeSelection', () => {
  const renderApp = (props) => render(<PartnerTypeSelection {...defaultProps} {...props} />);

  test('should render partner type selection screen', async () => {
    const setStep = jest.fn();
    renderApp({ setStep });
    expect(screen.getByText(/Choose your Partner Type/i)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Reseller Partner'));
    await userEvent.click(screen.getByText('Next'));
    expect(setStep).toHaveBeenCalledTimes(1);
  });
});
