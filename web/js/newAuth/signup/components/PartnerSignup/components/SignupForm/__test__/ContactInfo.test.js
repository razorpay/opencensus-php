import React from 'react';
import ContactInfo from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/ContactInfo';
import { render, screen, fireEvent, userEvent } from 'test-utils';
// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  setStep: () => {},
  setContactName: () => {},
};

describe('ContactInfo', () => {
  const renderApp = (props) => render(<ContactInfo {...defaultProps} {...props} />);

  test('should render contact info screen', async () => {
    const setStep = jest.fn();
    const { getByPlaceholderText } = renderApp({ setStep });
    expect(screen.getByText(/Enter Contact Details/i)).toBeInTheDocument();
    await fireEvent.change(getByPlaceholderText('Enter your name'), {
      target: { value: 'some name' },
    });
    await userEvent.click(screen.getByText('Next'));
    expect(setStep).toHaveBeenCalledTimes(1);
  });
});
