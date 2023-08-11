import React from 'react';
import { render } from 'common/services/test/test-utils';
import ContactDetails from 'merchant/views/PartnerDashboard/Activation/Components/mweb/ContactDetails';
import * as trackEvents from 'common/utils/analytics';
const analyticsTrackSpy = jest.spyOn(trackEvents, 'analyticsTrack');

const mockActivation = {
  data: {
    contact_details: {
      contact_name: { value: 'name' },
      contact_email: { value: 'email' },
      contact_mobile: { value: 'mobile' },
    },
  },
  postData: jest.fn(),
};
jest.mock('merchant/views/PartnerDashboard/Activation/Hooks/useActivation', () => ({
  __esModule: true,
  default: () => mockActivation,
}));

describe('mweb ContactDetails ', () => {
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

  test('Show Contact Details', () => {
    const props = {
      isFormLocked: false,
      partnerID: 'KBrJAIEqre5ucn',
    };
    render(<ContactDetails {...props} />);

    // Check track events on mount
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner KYC Form',
        actionName: 'Opened',
        screen: 'Contact Details',
      }),
    );
  });
});
