import React from 'react';
import { render, screen } from 'test-utils';
import VirtualAccountList from 'merchant/views/SmartCollect/VirtualAccounts/List';
import { defaultLocation } from 'merchant/views/SmartCollect/VirtualAccounts/__tests__/mocks/fixtures/List';
import { getInitialReduxState } from 'merchant/views/mocks/fixtures';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

global.rzpQ = {
  smartCollect: () => ({ interaction: jest.fn() }),
  component: jest.fn(),
};

export const reduxStateWithCustomerFeeBearer = getInitialReduxState({
  merchant: { fee_bearer: FEE_BEARER_TYPES.CUSTOMER },
});

describe('Virtual Accounts List', () => {
  test('should render disabled Create Customer Identifier button when fee_bearer is customer', () => {
    render(<VirtualAccountList location={defaultLocation} />, {
      initialState: reduxStateWithCustomerFeeBearer,
      renderViaRouteGuard: false,
    });
    expect(screen.getByRole('button', { name: 'Create Customer Identifier' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create Customer Identifier' })).toBeDisabled();
    expect(
      screen.getByText(
        'Smart Collect is not supported for merchants accepting payments as per the convenience fee model. To enable, click',
        { exact: false },
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toHaveAttribute(
      'href',
      '/app/payments-and-refunds-settings/capture-refund-settings',
    );
  });
});
