import SettingsModal from 'merchant/views/PaymentButton/SubscriptionButton/components/SettingsModal';
import React from 'react';
import { userEvent, render, screen, waitFor } from 'test-utils';

describe('SettingsModal', () => {
  const paymentButton = {
    id: '123',
    title: 'Test Button',
    settings: {
      payment_button_theme: 'dark',
    },
  };

  const App = ({ ...rest }) => {
    return <SettingsModal paymentButton={paymentButton} {...rest} />;
  };
  it('should render the modal header', () => {
    const { getByText } = render(<App />);
    expect(getByText('Button Settings')).toBeInTheDocument();
  });

  it('should hide custom message input when "Show custom message after a payment" is unchecked', async () => {
    const { getByLabelText, queryByPlaceholderText } = render(<App />);
    const checkbox = getByLabelText('Show custom message after a payment');
    await userEvent.click(checkbox);
    await userEvent.click(checkbox);
    expect(queryByPlaceholderText('Enter a custom message')).not.toBeInTheDocument();
    // TODO for future scope , add case when it is not visible
  });

  it('should disable save button when custom message input is empty', async () => {
    render(<App />);
    const checkbox = screen.getByRole('checkbox');
    await userEvent.click(checkbox);
    const saveButton = screen.getByRole('button', {
      name: 'Save',
    });
    expect(saveButton).toBeDisabled();
  });

  it('should enable save button when custom message input is not empty', async () => {
    const { getByText } = render(<SettingsModal />);
    const checkbox = screen.getByRole('checkbox');
    await userEvent.click(checkbox);
    const input = screen.getByRole('textbox');
    await userEvent.type(input, 'Thank you for your payment!');
    const saveButton = getByText('Save');
    expect(saveButton).toBeEnabled();
  });

  it('should call editPaymentButton and closeModal when save button is clicked', async () => {
    const editPaymentButton = jest.fn(() => Promise.resolve());
    render(<App editPaymentButton={editPaymentButton} />);
    const checkbox = screen.getByRole('checkbox');
    await userEvent.click(checkbox);
    const input = screen.getByRole('textbox');
    await userEvent.type(input, 'Thank you for your payment!');
    const saveButton = screen.getByRole('button', { name: 'Save' });
    await userEvent.click(saveButton);
    expect(saveButton).toBeDisabled();
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelButton);
    expect(cancelButton).toBeInTheDocument();
    await waitFor(() => expect(editPaymentButton).toHaveBeenCalled());
  });
});
