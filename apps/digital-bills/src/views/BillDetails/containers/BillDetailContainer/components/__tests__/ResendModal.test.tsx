import React, { useState } from 'react';
import { Button, SendIcon } from '@razorpay/blade/components';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent, waitFor } from '@apps/digital-bills/src/services/test/test-utils';
import ResendModal from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ResendModal';

describe('ResendModal', () => {
  function App({ formValues = { email: '', phoneNo: '' } }): React.ReactElement {
    const [isOpen, setIsOpen] = useState<boolean>(false);
    return (
      <>
        <Button
          icon={SendIcon}
          variant="tertiary"
          onClick={(): void => setIsOpen(true)}
          testID="resend-bill-btn"
        />
        <ResendModal
          onResend={() => undefined}
          modalProps={{ isOpen, onDismiss: () => setIsOpen(false) }}
          formValues={formValues}
        />
      </>
    );
  }

  test('should render the ResendModal component with default email and phone number', async () => {
    const formValues = {
      email: 'test@example.com',
      phoneNo: '1234567890',
    };

    const { getByTestId, getByText, getByRole } = renderWithWrappers(
      <App formValues={formValues} />,
    );
    const resendBtn = getByTestId('resend-bill-btn');
    await userEvent.click(resendBtn);
    expect(getByText('Resend Bill')).toBeInTheDocument();
    expect(getByRole('button', { name: 'Resend' })).toBeInTheDocument();
    expect(getByRole('button', { name: 'Cancel' })).toBeInTheDocument();

    const emailInput = getByRole('textbox', { name: 'Email Address' });
    const phoneInput = getByRole('textbox', { name: 'Phone Number' });

    expect(emailInput).toHaveValue(formValues.email);
    expect(phoneInput).toHaveValue(formValues.phoneNo);
  });

  test('should render the resend bill modal and closes it', async () => {
    const { getByTestId, getByText, queryByText, getByRole } = renderWithWrappers(<App />);
    const resendBtn = getByTestId('resend-bill-btn');
    await userEvent.click(resendBtn);
    expect(getByText('Resend Bill')).toBeInTheDocument();
    const dismissBtn = getByRole('button', { name: 'Cancel' });
    await userEvent.click(dismissBtn);
    await waitFor(() => expect(queryByText('Resend Bill')).toBeNull());
    expect(queryByText('Resend Bill')).toBeNull();
  });
});
