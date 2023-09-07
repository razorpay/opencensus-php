import React from 'react';

import { render, screen, userEvent } from 'common/services/test/test-utils';
import * as trackEvents from 'common/utils/analytics';
import ApplicationForm from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/ApplicationForm';
import { stepTestProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/__tests__/mocks/fixtures';
const analyticsTrackSpy = jest.spyOn(trackEvents, 'analyticsTrack');

describe('<ApplicationForm /> ', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  test('Application Form', async () => {
    render(<ApplicationForm {...stepTestProps} />);
    expect(screen.getByText('Fill in your details here')).toBeInTheDocument();

    // enter phone number
    const phoneNumberInput = await screen.getByText('Phone Number');
    await userEvent.type(phoneNumberInput, '9999888877');
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Migrate To PurePlatform Details',
        actionName: 'Entered',
      }),
    );

    // Reset mock for checking next event
    analyticsTrackSpy.mockClear();

    // enter website url
    const websiteURLInput = await screen.getByText('Your website URL');
    await userEvent.type(websiteURLInput, 'www.google.com');

    expect(analyticsTrackSpy).not.toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Migrate To PurePlatform Details',
        actionName: 'Entered',
      }),
    );

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));

    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Migrate To PurePlatform Details Next',
        actionName: 'Clicked',
      }),
    );
  });
});
