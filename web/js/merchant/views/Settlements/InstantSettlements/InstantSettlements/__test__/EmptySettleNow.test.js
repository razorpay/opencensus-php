import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import * as trackGa from 'common/utils/googleAnalytics';
import EmptySettleNow from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/EmptySettleNow';
import * as trackEvents from 'merchant/views/Settlements/trackEvents';
import { render, screen, userEvent, waitFor } from 'test-utils';

describe('EmptySettleNow', () => {
  const defaultProps = {
    showOndemandSettlementForm: jest.fn(),
  };

  const trackSpy = jest.spyOn(trackGa, 'setTrackData');
  const trackEventsSpy = jest.spyOn(trackEvents, 'trackSettleNowClicked');

  const renderApp = ({ props } = {}) => {
    render(<EmptySettleNow {...defaultProps} {...props} />);
  };

  beforeEach(() => {
    trackSpy.mockClear();
    trackEventsSpy.mockClear();
  });

  test('should render transaction banner heading and description', () => {
    renderApp();
    expect(screen.getByText('The wait is over...')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Delayed settlements can cause operational gaps for a business. Instant Settlements enables inflow of cash to amplify your business and meet cash requirements without any hassles - Instantly!',
      ),
    ).toBeInTheDocument();
  });

  test('should render transaction banner content', () => {
    renderApp();
    const bannerImages = screen.getAllByRole('img');
    expect(bannerImages[0]).toHaveAttribute('src', 'get_settlements_instantly.svg');
    expect(bannerImages[0]).toHaveAttribute('alt', 'Get settlements instantly');
    expect(bannerImages[1]).toHaveAttribute('src', 'early-settlement-light-blue.svg');
    expect(bannerImages[1]).toHaveAttribute('alt', 'Settle Now');
    expect(bannerImages[2]).toHaveAttribute('src', 'instant_settlement_no_transaction.svg');
    expect(bannerImages[2]).toHaveAttribute('alt', 'No transactions');
  });

  test('should render settle now and call track event when settle now clicked', async () => {
    renderApp();
    const settleNowButton = screen.getByRole('button');
    expect(settleNowButton).toBeInTheDocument();

    userEvent.click(settleNowButton);

    await waitFor(() => {
      expect(trackEventsSpy).toHaveBeenCalled();
    });
    expect(trackSpy).toHaveBeenCalled();
    expect(trackSpy).toHaveBeenCalledWith({
      eventCategory: 'Dashboard - Instant Settlement',
      eventAction: 'Click CTA - Settle Now',
      eventLabel: 'Instant Settlement | Empty State | Settle Now',
    });
    expect(defaultProps.showOndemandSettlementForm).toHaveBeenCalled();
  });
});
