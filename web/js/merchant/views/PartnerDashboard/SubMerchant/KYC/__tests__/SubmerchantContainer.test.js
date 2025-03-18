import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import SubmerchantContainer from 'merchant/views/PartnerDashboard/SubMerchant/KYC/submerchantContainer';
import { merchantActivationResponse } from 'merchant/views/PartnerDashboard/SubMerchant/KYC/__tests__/mocks/fixtures';
import User from 'merchant/models/User';
import store from 'merchant/store';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  // from route
  submerchantId: 'acc_submerchantId',
};

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            block_bank_details_update: {
              variables: {
                result: 'off',
              },
            },
          },
        }}
      />
    ),
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

describe('SubmerchantContainer', () => {
  beforeAll(() => {
    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: { I8KTdSuwTZ8dYt: { name: 'exposed', variables: { result: 'on' } } },
    };
  });

  const stateSpy = jest.spyOn(store, 'getState');

  beforeEach(() => {
    window.rzpQ = {
      onbr: () => ({ clicked: () => {}, interaction: () => {} }),
      component: jest.fn(),
    };
  });
  test('should render with default props', async () => {
    stateSpy.mockReturnValue({
      session: {
        org: {
          business_name: 'Razorpay',
          custom_code: 'rzp',
        },
      },
    });

    const initialState = {
      session: {
        user: new User({
          ...merchantActivationResponse.data,
          user: {
            settings: {},
          },
        }),
        org: {
          business_name: 'Razorpay',
          custom_code: 'rzp',
        },
      },
      activationWizard: {
        current_tab_name: 'jest tab',
      },
    };
    stateSpy.mockReturnValue(initialState);
    render(<SubmerchantContainer {...defaultProps} />, { initialState });
    await waitFor(() => {
      expect(
        screen.getByText(
          'We will deposit a small amount of money in your account to verify the account.',
        ),
      ).toBeVisible();
    });
  });
});
