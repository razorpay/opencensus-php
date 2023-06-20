import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import PurePlatformSwitchGuide from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch';
import { trackingExperimentsTestProp } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';

describe('<PurePlatformSwitchGuide /> ', () => {
  test('Show pure platform switch banner', () => {
    const props = {
      openModal: () => {},
      closeModal: () => {},
      trackingExperiments: trackingExperimentsTestProp,
    };
    render(<PurePlatformSwitchGuide {...props} />);
    expect(
      screen.getByText(
        'Seamlessly manage payments for your clients by integrating Razorpay APIs with your platform.',
      ),
    ).toBeInTheDocument();
  });
});
