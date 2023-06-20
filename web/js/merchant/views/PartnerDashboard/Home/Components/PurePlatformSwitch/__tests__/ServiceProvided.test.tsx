import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import ServiceProvided from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ServiceProvided';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';

describe('<ServiceProvided /> ', () => {
  test('Render Service Provided', () => {
    render(<ServiceProvided {...stepTestProps} />);
    expect(
      screen.getByText('Feel free to pick more than one that applies to your business'),
    ).toBeInTheDocument();
  });

  test('Select a service and click next', async () => {
    render(<ServiceProvided {...stepTestProps} />);
    const FreelancerServiceSelected = screen.getByTestId('Freelancers');
    const otherServiceSelected = screen.getByTestId('Others');

    expect(FreelancerServiceSelected).toBeInTheDocument();
    await userEvent.click(FreelancerServiceSelected);
    await userEvent.click(FreelancerServiceSelected);
    await userEvent.click(FreelancerServiceSelected);
    await userEvent.click(otherServiceSelected);
    await userEvent.click(FreelancerServiceSelected);

    const nextButton = screen.getByRole('button');
    expect(nextButton).toBeInTheDocument();
    await userEvent.click(nextButton);
    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });

    // select a non reseller type service and click next
    await userEvent.click(FreelancerServiceSelected); // removed already existing reseller type service
    const CRMServiceSelected = screen.getByTestId('CRM');
    await userEvent.click(CRMServiceSelected);
    await userEvent.click(nextButton);
    await waitFor(() => {
      expect(stepTestProps.setStep).toHaveBeenCalled();
    });
  });
});
