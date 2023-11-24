import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import userEvent from '@testing-library/user-event';

import * as home from 'merchant/reducers/home';
import * as profile from 'merchant/reducers/profile';
import * as details from 'merchant/reducers/settlements/details';
import * as analytics from 'merchant/views/Settlements/Settlements/analytics';
import SettlementsHeaderV2 from 'merchant/views/Settlements/components/SettlementsHeaderV2';
import * as modals from 'merchant_common/reducers/modals';
import { render, screen, waitFor, updateUseI18ServiceSpy } from 'test-utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

window.session_id = `12345`;

const state = {
  session: {
    user: {
      isAllowedEdit: () => true,
      findTag: () => false,
      activation_status: 'activated',
      international: false,
      merchant: {
        currency: 'inr',
      },
    },
    org: {},
  },
};

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
    render(<SettlementsHeaderV2 />);
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
    render(<SettlementsHeaderV2 />, { initialState: state });
    await waitFor(() => {
      expect(fetchPreviousSettlementsSpy).toHaveBeenCalledWith({
        count: 25,
        skip: 0,
      });
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
      expect(analyticsSpy).toHaveBeenCalledWith('Settlement Cycle', 'Clicked', {
        settlements_experiment_name: 'v1',
        sessionId: `12345`,
        state: 'Empty',
        activation_status: state.session.user.activation_status,
        international_payments_enabled: state.session.user.international,
        page: 'Home Screen',
      });
    });
    expect(screen.getByText('Documentation')).toBeInTheDocument();
    expect(screen.queryByText('http://razorpay.com/settlement'));
  });

  test('refresh button', async () => {
    const initialState = {
      session: {
        user: {
          isAllowedEdit: () => true,
          findTag: () => false,
          id: 'testing123',
          merchant: {
            currency: 'INR',
          },
        },
        org: {
          custom_code: 'rzp',
        },
      },
    };
    render(<SettlementsHeaderV2 />, { initialState });
    await waitFor(() => {
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(1);
    });
    expect(screen.getByText('Refresh')).toBeInTheDocument();
    userEvent.click(screen.getByText('Refresh'));
    await waitFor(() => {
      expect(fetchCurrentBalanceSpy).toHaveBeenCalledTimes(1);
      expect(fetchPreviousSettlementsSpy).toHaveBeenCalledWith({
        count: 25,
        skip: 0,
      });
      expect(fetchSettlementAmountSpy).toHaveBeenCalledTimes(1);
      expect(fetchSettlementConfigSpy).toHaveBeenCalledTimes(1);
      expect(fetchBankAccountChangeStatusSpy).toHaveBeenCalledWith('testing123');
      expect(fetchOnDemandFnSpy).toHaveBeenCalledTimes(2);
    });
  });

  test('documentation link for curlec orgs', async () => {
    const initialState = {
      session: {
        user: {
          isAllowedEdit: () => true,
          id: 'testing123',
          merchant: {
            currency: 'RM',
          },
        },
        org: {
          custom_code: 'curlec',
        },
      },
    };

    render(<SettlementsHeaderV2 />, { initialState });
    const url = 'https://curlec.com/docs/payments/settlements';
    await waitFor(() => {
      const docLink = screen.getByRole('link', { name: 'Documentation' });
      expect(docLink).toHaveAttribute('href', url);
    });
  });

  test('documentation link for rzp orgs', async () => {
    const initialState = {
      session: {
        user: {
          isAllowedEdit: () => true,
          id: 'testing123',
          merchant: {
            currency: 'IN',
          },
        },
        org: {
          custom_code: 'rzp',
        },
      },
    };

    render(<SettlementsHeaderV2 />, { initialState });
    const url = 'http://razorpay.com/settlement';
    await waitFor(() => {
      const docLink = screen.getByRole('link', { name: 'Documentation' });
      expect(docLink).toHaveAttribute('href', url);
    });
  });

  test('hide documentation link if documentation.documentation is enabled', async () => {
    const initialState = {
      session: {
        user: {
          isAllowedEdit: () => true,
          id: 'testing123',
          merchant: {
            currency: 'RM',
          },
        },
        org: {
          custom_code: 'curlec',
        },
      },
    };

    updateUseI18ServiceSpy('documentation.documentation');
    render(<SettlementsHeaderV2 />, { initialState });
    await waitFor(() => {
      expect(screen.queryByText('Documentation')).not.toBeInTheDocument();
    });
  });
});
