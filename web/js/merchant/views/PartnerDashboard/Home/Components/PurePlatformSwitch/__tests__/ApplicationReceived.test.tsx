import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import ApplicationReceived from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ApplicationReceived';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';

describe('<ApplicationReceived /> ', () => {
  test('Render Application Received', async () => {
    render(<ApplicationReceived {...stepTestProps} />);
    expect(
      screen.getByText('Our sales team will reach out to your for next steps'),
    ).toBeInTheDocument();
    expect(screen.getByText('We have received your request')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Our team will evaluate your responses and reach out to you in case more details are required.',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText('You can now manage your sub-merchant’s Transactions!'),
    ).toBeInTheDocument();

    const goToDashboardButton = screen.getByRole('button');
    expect(goToDashboardButton).toBeInTheDocument();
    await userEvent.click(goToDashboardButton);
    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });
  });
});
