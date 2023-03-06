import React from 'react';

import BusinessTypeSelection from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/BusinessTypeSelection';
import { render, screen, userEvent } from 'test-utils';
// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  setStep: () => {},
  setContactName: () => {},
  closeModal: () => {},
  showNotification: () => {},
  contactName: 'some name',
};

describe('BusinessTypeSelection', () => {
  const renderApp = (props) => render(<BusinessTypeSelection {...defaultProps} {...props} />);

  test('should render business type selection screen', async () => {
    const setStep = jest.fn();
    renderApp({ setStep });
    expect(screen.getByText(/Select Business Type/i)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Next'));
    expect(setStep).toHaveBeenCalledTimes(0);
  });
});
