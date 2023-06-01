import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import HaveAllCapabilities from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/HaveAllCapabilities';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';

describe('<HaveAllCapabilities /> ', () => {
  test('Render Have All Capabilities', async () => {
    render(<HaveAllCapabilities {...stepTestProps} />);
    expect(
      screen.getByText('You have all the capabilities to manage your clients'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'If you still feel you need more capabilities to manage your clients better reach out to us at partners@razorpay.com',
      ),
    ).toBeInTheDocument();
    const goToDashboardButton = screen.getByRole('button');
    expect(goToDashboardButton).toBeInTheDocument();
    await userEvent.click(goToDashboardButton);
    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });
  });
});
