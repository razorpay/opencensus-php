import React from 'react';

import { render, screen, userEvent } from 'common/services/test/test-utils';
import * as trackEvents from 'common/utils/analytics';
import Activation from 'merchant/views/PartnerDashboard/Activation';
const analyticsTrackSpy = jest.spyOn(trackEvents, 'analyticsTrack');

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
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        actionName: 'Opened',
        objectName: 'Partner KYC Form',
        screen: 'Contact Details',
      }),
    );
    expect(screen.getByText('Partner KYC Form')).toBeInTheDocument();
  });

  test('Test save form', async () => {
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
    // Discard mount event calls
    analyticsTrackSpy.mockClear();

    await userEvent.click(screen.getByText('Save'));
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        actionName: 'Saved',
        objectName: 'Partner KYC Form',
        screen: 'Contact Details',
      }),
    );
  });
});
