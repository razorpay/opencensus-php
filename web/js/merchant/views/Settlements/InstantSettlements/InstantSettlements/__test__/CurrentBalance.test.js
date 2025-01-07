import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import * as trackGa from 'common/utils/googleAnalytics';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import CurrentBalance from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/CurrentBalance';
import { render, screen, userEvent, waitFor } from 'test-utils';

const defaultProps = {
  showOndemandSettlementForm: jest.fn(),
  isBalanceLoading: false,
  merchantId: 'JkweuYH63G',
};

describe('CurrentBalance', () => {
  const trackSpy = jest.spyOn(trackGa, 'setTrackData');

  const renderApp = (props) => {
    render(<CurrentBalance {...defaultProps} {...props} />);
  };

  beforeEach(() => {
    trackSpy.mockClear();
  });

  test('should render current balance header and content', () => {
    renderApp();
    expect(screen.getByText('Current Balance')).toBeInTheDocument();

    const bankBalanceImg = screen.getAllByRole('img')?.[0];
    expect(bankBalanceImg).toBeInTheDocument();
    expect(bankBalanceImg).toHaveAttribute('src', 'bank-balance.svg');
  });

  test('should render current balance amount', () => {
    const props = {
      balance: 100,
    };
    renderApp(props);
    const formattedAmount = getFormattedAmount(props.balance);
    expect(screen.getByLabelText('amount-info').textContent).toContain(
      `₹ ${formattedAmount?.split('.')[0]}.${formattedAmount?.split('.')[1]}`,
    );
  });

  test('should render settle now button and call track event when settle now clicked', async () => {
    renderApp({
      checkIfFirstEverSettlement: jest.fn(),
    });
    expect(screen.getByText('Settle Now')).toBeInTheDocument();

    const settleNowButton = screen.getByRole('button');
    userEvent.click(settleNowButton);

    await waitFor(() => {
      expect(trackSpy).toHaveBeenCalled();
    });
    expect(trackSpy).toHaveBeenCalledWith({
      eventCategory: 'Dashboard - Instant Settlement',
      eventAction: 'Click CTA - Settle Now',
      eventLabel: 'Summary | Settle Now CTA',
    });
  });

  test('should render settle now restriction message when settle now is restricted', () => {
    const props = {
      settleNowRestrictionMsg:
        "You've already settled your maximum allowed limit of 10 times for the day.",
      isBalanceLoading: true,
    };
    renderApp(props);
    expect(screen.getByText(props.settleNowRestrictionMsg)).toBeInTheDocument();
  });
});
