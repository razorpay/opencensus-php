import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import ActivationWizard from 'merchant/components/Activation';
import store from 'merchant/store';

describe('<ActivationWizard /> ', () => {
  test('Check Email Verification for Partners', () => {
    const props = {
      user: {
        partner_type: 'aggregator',
        instantActivation: { isL1Submitted: false },
        user: { settings: '' },
      },
      store,
      data: {},
      categories: {},
      clarificationReasons: '',
      saveFile: () => {},
      trackEventsAction: () => {},
      submerchantId: '123',
      updateUser: () => {},
    };
    render(<ActivationWizard {...props} />);
    expect(screen.getByText('Contact Email')).toBeInTheDocument();
  });

  test('Check Email Verification not present if not a partner', () => {
    const props = {
      user: {
        partner_type: null,
        instantActivation: { isL1Submitted: false },
        user: { settings: '', confirmed: false },
      },
      store,
      data: {},
      categories: {},
      clarificationReasons: '',
      saveFile: () => {},
      trackEventsAction: () => {},
      submerchantId: '123',
      updateUser: () => {},
    };
    render(<ActivationWizard {...props} />);
    expect(screen.getByText('Contact Number')).toBeInTheDocument();
  });
});
