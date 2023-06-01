import React from 'react';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import Header from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import {
  trackingExperimentsTestProp,
  STEPS,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import EvaluateUseCase from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/EvaluateUseCase';
import HaveAllCapabilities from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/HaveAllCapabilities';
import ApplicationForm from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ApplicationForm';
import ApplicationReceived from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ApplicationReceived';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';

describe('<Header /> ', () => {
  test('Render Header for Various Steps', async () => {
    const props = {
      closeModal: () => {},
      setStep: () => {},
      step: STEPS.EVALUATE_USE_CASE,
      trackingExperiments: trackingExperimentsTestProp,
    };

    render(<Header {...props} />);
    render(<EvaluateUseCase {...stepTestProps} />);
    expect(screen.getByText('Want to manage your clients?')).toBeInTheDocument();
    const backButton = screen.getByTestId('back-id');
    await userEvent.click(backButton);

    props.step = STEPS.HAVE_ALL_CAPABILITIES;
    render(<Header {...props} />);
    render(<HaveAllCapabilities {...stepTestProps} />);
    await userEvent.click(backButton);

    props.step = STEPS.APPLICATION_FORM;
    render(<Header {...props} />);
    render(<ApplicationForm {...stepTestProps} />);
    await userEvent.click(backButton);

    props.step = STEPS.APPLICATION_RECEIVED;
    render(<Header {...props} />);
    render(<ApplicationReceived {...stepTestProps} />);
    await userEvent.click(backButton);
  });
});
