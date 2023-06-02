import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementsHeader from 'merchant/views/Settlements/components/SettlementsHeader';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import * as details from 'merchant/reducers/settlements/details';
import * as modals from 'merchant_common/reducers/modals';
import * as analytics from 'merchant/views/Settlements/Settlements/analytics';

jest.mock('merchant/views/Settlements/components/BalanceDetails', () => ({ amount }) => (
  <div>
    <h3>Balance details</h3>
    <span>Current Balance:</span>
    <span>{amount}</span>
  </div>
));

jest.mock(
  'merchant/views/Settlements/components/SettleNow',
  // prettier-ignore
  () => ({ showOndemandSettlementForm }) => (
    <>
      <div>Settle Now Widget</div>
      <button type="button" onClick={showOndemandSettlementForm}>
        Settle Now
      </button>
    </>
  ),
);

const state = {
  session: {
    user: {
      isOndemandSettlementEnabled: true,
      isOrgAllowedFunctionality: () => true,
      isAllowedView: () => true,
      findTag: jest.fn(),
    },
    mode: 'live',
    org: {},
  },
  home: {
    current_balance: {
      data: {
        id: 'G8q6Bh9LimvyMq',
        merchant_id: 'G8SGSNU5DTMAQi',
        type: 'primary',
        currency: 'INR',
        name: null,
        balance: 0,
        credits: 0,
        fee_credits: 0,
        refund_credits: 0,
        account_number: null,
        account_type: null,
        channel: null,
        updated_at: 1659685946,
        locked_balance: 0,
        last_fetched_at: null,
      },
    },
    settlement_amount: {
      data: {
        balance: 0,
        balance_currency: 'INR',
        settlement_amount: 0,
        settlement_currency: 'INR',
        next_settlement_time: null,
      },
    },
  },
  settlement: {
    holidayList: { data: {} },
    config: { data: {} },
    settleNowButtonDisabled: {
      data: {
        blocked: false,
      },
    },
  },
};

describe('SettlementsHeader', () => {
  const fetchOnDemandFnSpy = jest.spyOn(details, 'fetchOnDemandBlocked');
  const modalsSpy = jest.spyOn(modals, 'openModal');
  const analyticsSpy = jest.spyOn(analytics, 'handleAnalytics');

  const App = ({ initialState = state, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <SettlementsHeader {...rest} />
      </Provider>
    );
  };

  beforeEach(() => {
    fetchOnDemandFnSpy.mockClear();
    modalsSpy.mockClear();
    analyticsSpy.mockClear();
  });

  test('should call fetch on demand blocked on mount', async () => {
    render(<App />);
    await waitFor(() => {
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(1);
    });
  });

  describe('Left content', () => {
    test('should render balance details if allowed funtionality of current balance', () => {
      render(<App />);
      expect(screen.getByText('Balance details')).toBeInTheDocument();
      expect(screen.getByText('Current Balance:')).toBeInTheDocument();
    });

    test('should render settle now button if isOndemandSettlementEnabled and early settlement view alowed', () => {
      render(<App />);
      expect(screen.getByText('Settle Now')).toBeInTheDocument();
    });
  });
  describe('Right content', () => {
    test('should render how settlement work button', () => {
      render(<App />);
      expect(screen.getByText(/How settlements work?/)).toBeInTheDocument();
      expect(screen.getByRole('link')).toHaveAttribute('href', 'http://razorpay.com/settlement');
    });

    test('should render view settlement cycle', () => {
      render(<App />);
      expect(screen.getByText(/View Settlement Cycle/)).toBeInTheDocument();
    });

    test('should call openModal, rzpAnalytics and handleAnalytics on view settlement cycle click', async () => {
      render(<App />);
      userEvent.click(screen.getByText(/View Settlement Cycle/));
      await waitFor(() => {
        expect(modalsSpy).toHaveBeenCalledTimes(1);
        expect(window.rzpAnalytics).toHaveBeenCalledWith({
          eventCategory: 'Settlement Revamp',
          eventAction: 'View Settlement Cycle',
          eventLabel: `Settlements`,
        });
        expect(analyticsSpy).toHaveBeenCalledTimes(1);
        expect(analyticsSpy).toHaveBeenCalledWith('settlement cycle', 'clicked');
      });
    });
  });
});
