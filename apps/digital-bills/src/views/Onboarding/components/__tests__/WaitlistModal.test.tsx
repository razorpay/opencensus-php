import React, { useState } from 'react';
import { Button, TrashIcon } from '@razorpay/blade/components';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { waitFor, userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import WaitlistModal from '@apps/digital-bills/src/views/Onboarding/components/WaitlistModal';
import { STORES_COUNT_OPTIONS } from '@apps/digital-bills/src/views/Onboarding/constants';

type AppProps = {
  isLoading: boolean;
  isInWaitlist: boolean;
};

describe('WaitlistModal', () => {
  const handleSubmit = jest.fn();
  function App({ isLoading, isInWaitlist }: AppProps): React.ReactElement {
    const [isOpen, setIsOpen] = useState<boolean>(false);
    return (
      <>
        <Button
          icon={TrashIcon}
          variant="tertiary"
          onClick={(): void => setIsOpen(true)}
          testID="join-waitlist-btn"
        />
        <WaitlistModal
          onSubmit={handleSubmit}
          modalProps={{ isOpen, onDismiss: () => setIsOpen(false) }}
          isInWaitlist={isInWaitlist}
          isLoading={isLoading}
        />
      </>
    );
  }
  test('should render the waitlist modal', async () => {
    const { getByTestId, getByText, getByRole } = renderWithWrappers(
      <App isLoading={false} isInWaitlist={false} />,
    );
    const joinWaitlistBtn = getByTestId('join-waitlist-btn');
    await userEvent.click(joinWaitlistBtn);

    // Modal title
    expect(getByText('Join The Waitlist!')).toBeInTheDocument();

    // Modal sub-title
    expect(
      getByText(
        'To better assist your needs, please tell us how many stores do you operate currently:',
      ),
    ).toBeInTheDocument();

    // Modal body - Chip group
    STORES_COUNT_OPTIONS.forEach((option) => {
      expect(getByText(option.label)).toBeInTheDocument();
    });

    // Modal CTA buttons
    expect(getByRole('button', { name: 'Submit' })).toBeInTheDocument();
    expect(getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
  });

  test('should render the WaitlistModal and closes it on clicking cancel', async () => {
    const { getByTestId, getByText, queryByText, getByRole } = renderWithWrappers(
      <App isLoading={false} isInWaitlist={false} />,
    );
    const joinWaitlistBtn = getByTestId('join-waitlist-btn');
    await userEvent.click(joinWaitlistBtn);
    expect(getByText('Join The Waitlist!')).toBeInTheDocument();
    const cancelBtn = getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelBtn);
    await waitFor(() => expect(queryByText('Join The Waitlist!')).toBeNull());
  });

  test("should invoke 'onSubmit' prop function passed, when 'Submit' button is clicked", async () => {
    const { getByTestId, getByText, getByRole } = renderWithWrappers(
      <App isLoading={false} isInWaitlist={false} />,
    );
    const joinWaitlistBtn = getByTestId('join-waitlist-btn');
    await userEvent.click(joinWaitlistBtn);
    expect(getByText('Join The Waitlist!')).toBeInTheDocument();
    const modalSubmitBtn = getByRole('button', { name: 'Submit' });
    expect(modalSubmitBtn).toBeInTheDocument();

    // 'Submit' button should be disabled, when stores count range is not selected
    expect(modalSubmitBtn).toBeDisabled();

    // 'Submit' button should be enabled, when stores count range is selected
    await userEvent.click(getByText('50+'));
    expect(modalSubmitBtn).not.toBeDisabled();

    await userEvent.click(modalSubmitBtn);
    expect(handleSubmit).toHaveBeenCalledTimes(1);
  });

  test("should have 'Submit' button in disabled state, when 'isInWaitlist' prop is true", async () => {
    const { getByTestId, getByText, getByRole } = renderWithWrappers(
      <App isLoading={false} isInWaitlist={true} />,
    );
    const joinWaitlistBtn = getByTestId('join-waitlist-btn');
    await userEvent.click(joinWaitlistBtn);
    expect(getByText('Join The Waitlist!')).toBeInTheDocument();
    const modalSubmitBtn = getByRole('button', { name: 'Submit' });
    expect(modalSubmitBtn).toBeInTheDocument();
    expect(modalSubmitBtn).toBeDisabled();
  });

  test("should have 'Submit' and 'Cancel' buttons in disabled state, when 'isLoading' prop is true", async () => {
    const { getByTestId, getByText, getByRole } = renderWithWrappers(
      <App isLoading={true} isInWaitlist={false} />,
    );
    const joinWaitlistBtn = getByTestId('join-waitlist-btn');
    await userEvent.click(joinWaitlistBtn);
    expect(getByText('Join The Waitlist!')).toBeInTheDocument();

    // Cancel button
    const modalCancelBtn = getByRole('button', { name: 'Cancel' });
    expect(modalCancelBtn).toBeInTheDocument();
    expect(modalCancelBtn).toBeDisabled();

    // Submit button - should be in loading state
    const modalSubmitBtn = getByRole('button', { name: 'Submit' });
    expect(modalSubmitBtn).toBeInTheDocument();
    expect(modalSubmitBtn).toBeDisabled();
  });
});
