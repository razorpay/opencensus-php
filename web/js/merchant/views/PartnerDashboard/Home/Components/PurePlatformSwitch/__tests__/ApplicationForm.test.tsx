import React from 'react';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import ApplicationForm from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ApplicationForm';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';

describe('<ApplicationForm /> ', () => {
  test('Application Form', async () => {
    render(<ApplicationForm {...stepTestProps} />);
    expect(screen.getByText('Fill in your details here')).toBeInTheDocument();

    // enter phone number
    const phoneNumberInput = await screen.getByText('Phone Number');
    await userEvent.type(phoneNumberInput, '9999888877');

    // enter website url
    const websiteURLInput = await screen.getByText('Your website URL');
    await userEvent.type(websiteURLInput, 'www.google.com');

    userEvent.click(screen.getByRole('button', { name: 'Next' }));
  });
});
