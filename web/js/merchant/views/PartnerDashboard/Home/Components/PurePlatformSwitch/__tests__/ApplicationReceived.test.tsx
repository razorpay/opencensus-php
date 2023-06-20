import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import ApplicationReceived from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ApplicationReceived';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';
import * as flagAction from 'merchant/reducers/partner';

let setPartnerSwitchSpy;

describe('<ApplicationReceived /> ', () => {
  setPartnerSwitchSpy = jest.spyOn(flagAction, 'setPartnerSwitchFlag');
  test('Render Application Received', async () => {
    render(<ApplicationReceived {...stepTestProps} />);
    expect(
      screen.getByText('Our sales team will reach out to you for the next steps'),
    ).toBeInTheDocument();
    expect(screen.getByText('We have received your request')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Our team will evaluate your response and contact you for more details if needed.',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText("You can now manage your sub-merchants' transactions!"),
    ).toBeInTheDocument();

    const goToDashboardButton = screen.getByRole('button');
    expect(goToDashboardButton).toBeInTheDocument();
    await userEvent.click(goToDashboardButton);
    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });
    expect(setPartnerSwitchSpy).toHaveBeenCalled();
  });

  test('Render Application Received', () => {
    jest.mock('common/utils/rzp-utils', () => () => true);
    render(<ApplicationReceived {...stepTestProps} />);
    expect(screen.getByText('Application Received')).toBeInTheDocument();
  });
});
