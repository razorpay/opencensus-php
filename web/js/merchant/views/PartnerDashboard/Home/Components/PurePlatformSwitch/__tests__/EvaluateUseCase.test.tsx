import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import EvaluateUseCase from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/EvaluateUseCase';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';

describe('<EvaluateUseCase /> ', () => {
  test('Render Evaluate Use Case', () => {
    render(<EvaluateUseCase {...stepTestProps} />);
    expect(
      screen.getByText('Do you have a product to manage payments for your clients?'),
    ).toBeInTheDocument();
    expect(screen.getByText('Answer keeping your business in mind')).toBeInTheDocument();
    expect(
      screen.getByText(
        'You will need O -Auth integration to start receiving commissions once you complete the switch',
      ),
    ).toBeInTheDocument();
  });

  test('Selecting response to question and clicking next', async () => {
    render(<EvaluateUseCase {...stepTestProps} />);

    // if clicked yes
    const yesButton = screen.getByText('Yes');
    expect(yesButton).toBeInTheDocument();
    await userEvent.click(yesButton);
    const nextButton = screen.getByText('Next');
    expect(nextButton).toBeInTheDocument();
    await userEvent.click(nextButton);

    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });

    // if clicked no
    const noButton = screen.getByText('No');
    expect(noButton).toBeInTheDocument();
    await userEvent.click(noButton);
    await userEvent.click(nextButton);
    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });
  });
});
