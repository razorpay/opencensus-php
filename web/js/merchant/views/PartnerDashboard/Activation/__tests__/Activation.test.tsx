import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import Activation from 'merchant/views/PartnerDashboard/Activation';

describe('<Activation /> ', () => {
  beforeAll(() => {
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });
  test('Show Activation Form', () => {
    const props = {
      showPartnerKYCStatusModal: () => {},
      showNotification: () => {},
      hidePartnerKYCStatusModal: () => {},
      tracking: {
        trackEvent: () => {},
      },
      user: {
        merchant: { id: '123' },
      },
      showKYCStatus: false,
      kycStatusModalType: '',
    };
    render(<Activation {...props} />);
    expect(screen.getByText('Partner KYC Form')).toBeInTheDocument();
  });
});
