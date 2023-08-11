import React from 'react';
import { render } from 'common/services/test/test-utils';
import BusinessDetails from 'merchant/views/PartnerDashboard/Activation/Components/mweb/BusinessDetails';
import * as trackEvents from 'common/utils/analytics';
const analyticsTrackSpy = jest.spyOn(trackEvents, 'analyticsTrack');

const mockActivation = {
  data: {
    business_details: {
      business_type: { value: 'business_type' },
      company_pan: { value: 'company_pan' },
      business_name: { value: 'business_name' },
      promoter_pan: { value: 'promoter_pan' },
      promoter_pan_name: { value: 'promoter_pan_name' },
      bank_account_name: { value: 'bank_account_name' },
      bank_account_number: { value: 'bank_account_number' },
      bank_branch_ifsc: { value: 'bank_branch_ifsc' },
      gstin: { value: 'gstin' },
    },
  },
  postData: jest.fn(),
};
jest.mock('merchant/views/PartnerDashboard/Activation/Hooks/useActivation', () => ({
  __esModule: true,
  default: () => mockActivation,
}));

describe('mweb BusinessDetails', () => {
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

  test('Show Business Details', () => {
    const props = {
      isFormLocked: false,
      isFormSubmitted: false,
      partnerID: 'KBrJAIEqre5ucn',
    };
    render(<BusinessDetails {...props} />);

    // Check track events on mount
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner KYC Form',
        actionName: 'Opened',
        screen: 'Business Details',
      }),
    );
  });
});
