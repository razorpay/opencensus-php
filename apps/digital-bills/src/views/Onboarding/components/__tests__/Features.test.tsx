import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { waitFor, userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import Features from '@apps/digital-bills/src/views/Onboarding/components/Features';

describe('Features', () => {
  const updateActiveScreen = jest.fn();
  test('should render the Features page', () => {
    const { queryByText, getByText, getByRole } = renderWithWrappers(
      <Features updateActiveScreen={updateActiveScreen} isInWaitlist={false} />,
    );
    const alertBanner = queryByText(
      'Your request has been recorded and we will get back to you as soon as possible.',
    );
    expect(alertBanner).not.toBeInTheDocument();
    expect(getByText('What makes BillMe great?')).toBeInTheDocument();
    expect(getByRole('link', { name: 'Know More' })).toBeInTheDocument();
    expect(getByRole('link', { name: /Back/ })).toBeInTheDocument();
    const joinWaitlistBtn = getByRole('button', { name: 'Join The Waitlist' });
    expect(joinWaitlistBtn).toBeInTheDocument();
    expect(joinWaitlistBtn).not.toBeDisabled();
  });

  test("should render Features page accordingly when 'isInWaitlist' prop is true", () => {
    const { getByText, queryByRole, getByRole } = renderWithWrappers(
      <Features updateActiveScreen={updateActiveScreen} isInWaitlist />,
    );
    const alertBanner = getByText(
      'Your request has been recorded and we will get back to you as soon as possible.',
    );
    expect(alertBanner).toBeInTheDocument();
    expect(queryByRole('link', { name: /Back/ })).not.toBeInTheDocument();
    const joinWaitlistBtn = getByRole('button', { name: 'Join The Waitlist' });
    expect(joinWaitlistBtn).toBeInTheDocument();
    expect(joinWaitlistBtn).toBeDisabled();
  });

  test("should invoke 'updateActiveScreen' method when 'Back' button is clicked", async () => {
    const { getByRole } = renderWithWrappers(
      <Features updateActiveScreen={updateActiveScreen} isInWaitlist={false} />,
    );
    const backBtn = getByRole('link', { name: /Back/ });
    await userEvent.click(backBtn);
    expect(updateActiveScreen).toHaveBeenCalledTimes(1);
  });

  test("should display 'Waitlist Modal', when 'Join The Waitlist' button is clicked", async () => {
    const { getByRole, queryByRole } = renderWithWrappers(
      <Features updateActiveScreen={updateActiveScreen} isInWaitlist={false} />,
    );
    const joinWaitlistBtn = getByRole('button', { name: 'Join The Waitlist' });
    await userEvent.click(joinWaitlistBtn);
    await waitFor(() => {
      expect(getByRole('button', { name: 'Submit' })).toBeInTheDocument();
    });

    const modalCancelBtn = getByRole('button', { name: 'Cancel' });
    await userEvent.click(modalCancelBtn);
    await waitFor(() => {
      expect(queryByRole('button', { name: 'Submit' })).not.toBeInTheDocument();
    });
  });
});
