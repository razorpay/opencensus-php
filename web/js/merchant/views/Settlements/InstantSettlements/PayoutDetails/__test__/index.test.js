import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'test-utils';
import { state } from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/__test__/mocks/fixtures';
import PayoutDetails from 'merchant/views/Settlements/InstantSettlements/PayoutDetails';
import * as trackGa from 'common/utils/googleAnalytics';
import * as settlementActions from 'merchant/reducers/instantSettlements/details';
import * as trackEvents from 'merchant/views/Settlements/trackEvents';
import { titleCase } from 'common/utils/rzp-utils';

describe('PayoutDetails', () => {
  const trackSpy = jest.spyOn(trackGa, 'setTrackData');
  const settlementActionsSpy = jest.spyOn(settlementActions, 'fetchItem');
  const settlementAmountSpy = jest.spyOn(settlementActions, 'fetchTotalSettlementAmount');
  const trackHoverSpy = jest.spyOn(trackEvents, 'trackOnDemandPayoutDeductionsHover');

  const settlementId = 'setlod_InXEtIiuvilPhM';
  const initialEntry = `/instantsettlement_details/${settlementId}`;

  const renderApp = ({ initialState = state, props } = {}) =>
    render(<PayoutDetails {...props} />, {
      path: '/instantsettlement_details/:id',
      initialState,
      initialEntries: [initialEntry],
    });

  beforeEach(() => {
    jest.useFakeTimers();
    trackSpy.mockClear();
    settlementActionsSpy.mockClear();
    settlementAmountSpy.mockClear();
    trackHoverSpy.mockClear();
  });

  test('should call track payout details on mount', async () => {
    renderApp();
    await waitFor(() => {
      expect(trackSpy).toHaveBeenCalledTimes(1);
      expect(trackSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'View Payout Details Page',
        eventLabel: 'Payout Details Page | View batch payout details',
      });
    });
  });

  test('should call fetch settlement items from settlement id on mount', async () => {
    renderApp();
    await waitFor(() => {
      expect(settlementActionsSpy).toHaveBeenCalledTimes(1);
      expect(settlementActionsSpy).toHaveBeenCalledWith(settlementId);
    });
  });

  test('should fetch total settlement amount when amount settled is zero', async () => {
    renderApp();
    await waitFor(() => {
      expect(settlementAmountSpy).toHaveBeenCalled();
      expect(settlementAmountSpy).toHaveBeenCalledWith(settlementId);
    });
  });

  describe('PayoutBreadcrumb', () => {
    test('should render payout detail on-demand settlement tab', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Ondemand Settlements')).toBeInTheDocument();
        expect(screen.getByText('Details')).toBeInTheDocument();
      });
    });

    test('should track instant settlements payout details and redirect on clicking of breadcrumb', async () => {
      renderApp();
      await waitFor(() => {
        expect(
          screen.getByRole('link', {
            name: 'Ondemand Settlements',
          }),
        ).toBeInTheDocument();
      });
      const payoutButton = screen.getByRole('link', {
        name: 'Ondemand Settlements',
      });
      userEvent.click(payoutButton);
      await waitFor(() => {
        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Click - Go back Instant Settlements',
          eventLabel: 'Payout Details Page | Go back to instant settlements',
        });
      });
    });
  });

  describe('PayoutDetailsSection', () => {
    test('should render payout detail section heading', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Total Settled Amount')).toBeInTheDocument();
      });
    });

    test('should render total settlement amount when amount settled is zero and status is either created or initiated', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByTestId('settlement-info')).toBeInTheDocument();
        expect(trackSpy).toHaveBeenCalledTimes(1);
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'View Payout Details Page',
          eventLabel: 'Payout Details Page | View batch payout details',
        });
      });
      const infoIcon = screen.getByTestId('settlement-info');
      userEvent.hover(infoIcon);
      await waitFor(() => {
        expect(
          screen.getByText(
            'We are fetching Total Settled Amount, and it seems that some of the settlements are taking longer than expected.',
          ),
        ).toBeInTheDocument();

        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Hover - Total Settled Amount Info Icon',
          eventLabel: 'Payout Details Page | Total Settled Amount info icon',
        });
      });
    });

    test('should render settled amount and deductions', async () => {
      const initialState = {
        ...state,
        instantSettlement: {
          ...state.instantSettlement,
          instantSettlement: {
            ...state.instantSettlement.instantSettlement,
            amount_settled: 2540.59,
          },
        },
      };
      renderApp({ initialState });
      await waitFor(() => {
        expect(screen.getAllByText('Requested Amount')[0]).toBeInTheDocument();
        expect(trackSpy).toHaveBeenCalledTimes(1);
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'View Payout Details Page',
          eventLabel: 'Payout Details Page | View batch payout details',
        });
      });

      const deductionIcon = screen.getByTestId('settlement-deduction');
      userEvent.hover(deductionIcon);
      await waitFor(() => {
        expect(screen.getByText('Ondemand Fee')).toBeInTheDocument();
        expect(screen.getByText('Tax')).toBeInTheDocument();
        expect(screen.getAllByText('Deductions')[0]).toBeInTheDocument();

        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Hover - Deduction Info Icon',
          eventLabel: 'Payout Details Page | Hover Deductions info icon',
        });

        expect(trackHoverSpy).toHaveBeenCalled();
      });
    });
  });

  describe('PayoutMetaDetails', () => {
    test('should render payout meta details', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Status')).toBeInTheDocument();
        expect(
          screen.getByText(titleCase(state.instantSettlement.instantSettlement.status)),
        ).toBeInTheDocument();
        expect(screen.getByText('Settlement Id')).toBeInTheDocument();
        expect(screen.getByText(state.instantSettlement.instantSettlement.id)).toBeInTheDocument();
      });
    });

    test('should render payout details breakup', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Ondemand Settlement Breakup')).toBeInTheDocument();
        expect(screen.getByText('Breakup List')).toBeInTheDocument();
      });
    });

    test('should render payout details section', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Details List Container')).toBeInTheDocument();
        expect(
          screen.getByText(state.instantSettlement.instantSettlement.entity),
        ).toBeInTheDocument();
        expect(
          screen.getByText('The amount that gets settled to your bank account will show up here.'),
        ).toBeInTheDocument();
      });
    });
  });
});
