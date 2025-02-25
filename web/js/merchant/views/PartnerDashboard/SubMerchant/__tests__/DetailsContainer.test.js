import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import DetailsContainer from 'merchant/views/PartnerDashboard/SubMerchant/DetailsContainer';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  id: 'submerchantId',
  appId: 'appId',
};
const initialState = {
  session: {
    user: {
      isPartner: () => true,
      isFeatureEnabled: () => true,
      isSubMerchantKycEnabled: true,
    },
  },
};

describe('DetailsContainer', () => {
  beforeEach(() => {
    window.rzpQ = {
      onbr: () => ({ clicked: () => {}, interaction: () => {} }),
      component: jest.fn(),
    };
    jest.setSystemTime(new Date(2023, 2, 31));
  });

  test('should render with default props', async () => {
    render(<DetailsContainer {...defaultProps} />, { initialState });
    await waitFor(() => {
      expect(screen.getByText('REQUEST KYC APPROVAL')).toBeVisible();
      expect(screen.getByText('acc_LY0LBrSgJLlFHa')).toBeVisible();
      expect(screen.getByText('March 31, 2023')).toBeVisible();
    });
  });
});
