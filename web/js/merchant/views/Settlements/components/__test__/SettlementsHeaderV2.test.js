import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementsHeaderV2 from 'merchant/views/Settlements/components/SettlementsHeaderV2';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';
import * as details from 'merchant/reducers/settlements/details';
import * as home from 'merchant/reducers/home';
import * as profile from 'merchant/reducers/profile';
import * as modals from 'merchant_common/reducers/modals';
import * as analytics from 'merchant/views/Settlements/Settlements/analytics';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

describe('SettlementsHeaderV2', () => {
  const fetchOnDemandFnSpy = jest.spyOn(details, 'fetchOnDemandBlocked');
  const fetchSettlementConfigSpy = jest.spyOn(details, 'fetchSettlementConfig');
  const fetchPreviousSettlementsSpy = jest.spyOn(details, 'fetchPreviousSettlements');
  const fetchCurrentBalanceSpy = jest.spyOn(home, 'fetchCurrentBalance');
  const fetchSettlementAmountSpy = jest.spyOn(home, 'fetchSettlementAmount');
  const fetchBankAccountChangeStatusSpy = jest.spyOn(profile, 'fetchBankAccountChangeStatus');
  const openModalsSpy = jest.spyOn(modals, 'openModal');
  const closeModalsSpy = jest.spyOn(modals, 'closeModal');
  const analyticsSpy = jest.spyOn(analytics, 'handleAnalytics');
  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <SettlementsHeaderV2 {...rest} />
      </Provider>
    );
  };

  beforeEach(() => {
    fetchOnDemandFnSpy.mockClear();
    fetchSettlementConfigSpy.mockClear();
    fetchPreviousSettlementsSpy.mockClear();
    fetchCurrentBalanceSpy.mockClear();
    fetchSettlementAmountSpy.mockClear();
    fetchBankAccountChangeStatusSpy.mockClear();
    openModalsSpy.mockClear();
    closeModalsSpy.mockClear();
    analyticsSpy.mockClear();
  });

  test('should render SettlementsHeaderV2', async () => {
    render(<App />);
    await waitFor(() => {
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(1);
    });
    expect(screen.getByText('Overview')).toBeInTheDocument();
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('Settlement due today')).toBeInTheDocument();
    expect(screen.getByText('Previous settlement')).toBeInTheDocument();
    expect(screen.getByText('Upcoming settlement')).toBeInTheDocument();
    expect(screen.getByText('My Settlement Cycle')).toBeInTheDocument();
  });

  test('My settlement cycle', async () => {
    render(<App />);
    await waitFor(() => {
      expect(fetchPreviousSettlementsSpy).toHaveBeenCalledTimes(1);
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(1);
    });
    expect(screen.getByText('My Settlement Cycle')).toBeInTheDocument();
    userEvent.click(screen.getByText('My Settlement Cycle'));
    await waitFor(() => {
      expect(openModalsSpy).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventCategory: 'Settlement Revamp',
        eventAction: 'View Settlement Cycle',
        eventLabel: `Settlements`,
      });
      expect(analyticsSpy).toHaveBeenCalledTimes(1);
      expect(analyticsSpy).toHaveBeenCalledWith('settlement cycle', 'clicked');
    });
    expect(screen.getByText('Documentation')).toBeInTheDocument();
    expect(screen.getByRole('link')).toHaveAttribute('href', 'http://razorpay.com/settlement');
  });

  test('refresh button', async () => {
    render(<App />);
    await waitFor(() => {
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(1);
    });
    expect(screen.getByText('Refresh')).toBeInTheDocument();
    userEvent.click(screen.getByText('Refresh'));
    await waitFor(() => {
      expect(fetchCurrentBalanceSpy).toHaveBeenCalledTimes(1);
      expect(fetchPreviousSettlementsSpy).toHaveBeenCalledTimes(2);
      expect(fetchSettlementAmountSpy).toHaveBeenCalledTimes(1);
      expect(fetchSettlementConfigSpy).toHaveBeenCalledTimes(1);
      expect(fetchBankAccountChangeStatusSpy).toHaveBeenCalledTimes(1);
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(2);
    });
  });
});
