import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'test-utils';
import * as trackGa from 'common/utils/googleAnalytics';
import * as settlementActions from 'merchant/reducers/instantSettlements/details';
import * as trackEvents from 'merchant/views/Settlements/trackEvents';
import * as ModalActions from 'merchant_common/reducers/modals';
import { titleCase } from 'common/utils/rzp-utils';
import { state, defaultProps } from './mocks/fixtures';
import InstantSettlementDetails from 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails';

describe('InstantSettlementDetails', () => {
  const trackGaSpy = jest.spyOn(trackGa, 'setTrackData');
  const trackHoverEventsSpy = jest.spyOn(trackEvents, 'trackOnDemandHoverInfo');
  const trackIdDetailsEventsSpy = jest.spyOn(trackEvents, 'trackOnDemandIdDetails');
  const trackViewMoreClickEventsSpy = jest.spyOn(trackEvents, 'trackOnDemandViewMoreClick');
  const fetchItemSpy = jest.spyOn(settlementActions, 'fetchItem');
  const fetchAmountSpy = jest.spyOn(settlementActions, 'fetchTotalSettlementAmount');
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

  const renderApp = ({ initialState = state, props } = {}) =>
    render(<InstantSettlementDetails {...defaultProps} {...props} />, {
      initialState,
    });

  beforeEach(() => {
    trackGaSpy.mockClear();
    trackHoverEventsSpy.mockClear();
    trackIdDetailsEventsSpy.mockClear();
    trackViewMoreClickEventsSpy.mockClear();
    fetchItemSpy.mockClear();
    fetchAmountSpy.mockClear();
    closeModalSpy.mockClear();
  });

  test('should call fetch items on mount', async () => {
    renderApp();
    await waitFor(() => {
      expect(fetchItemSpy).toHaveBeenCalled();
    });
    expect(fetchItemSpy).toHaveBeenCalledWith(defaultProps.id);
  });

  describe('InstantSettlementDetails', () => {
    test('should call track event on mounting details', async () => {
      renderApp();
      await waitFor(() => {
        expect(trackIdDetailsEventsSpy).toHaveBeenCalledTimes(1);
      });
      expect(trackIdDetailsEventsSpy).toHaveBeenCalledWith(
        state.instantSettlement.instantSettlement,
      );
    });
  });

  describe('InstantSettlementPanel', () => {
    test('should render instant settlement panel heading', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText(/Settlement Id:/i)).toBeInTheDocument();
      });
      expect(screen.getByText(state.instantSettlement.instantSettlement.id)).toBeInTheDocument();
    });

    test('should render instant settlement section heading and entity details', async () => {
      const utr = 'qlirejc';
      renderApp({
        initialState: {
          instantSettlement: {
            ...state.instantSettlement,
            instantSettlement: {
              ...state.instantSettlement.instantSettlement,
              scheduled: true,
              amount_settled: 1000,
              ondemand_payouts: {
                ...state.instantSettlement.instantSettlement.ondemand_payouts,
                count: 1,
                items: [
                  {
                    id: 'setlodp_InXEtJ23TyveQt',
                    amount: 10000,
                    amount_settled: 9971,
                    fees: 29,
                    utr,
                    status: 'initiated',
                    created_at: 1643017682,
                  },
                ],
              },
            },
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('Settlement Details')).toBeInTheDocument();
      });
      expect(screen.getByText('Status')).toBeInTheDocument();
      expect(
        screen.getByText(titleCase(state.instantSettlement.instantSettlement.status)),
      ).toBeInTheDocument();
      expect(screen.getByText('Pending Amount')).toBeInTheDocument();
      expect(screen.getByText('Transaction Fee')).toBeInTheDocument();
      expect(screen.getByText('GST Charge')).toBeInTheDocument();
      expect(screen.getByText('Type')).toBeInTheDocument();
      expect(screen.getByText('Instant')).toBeInTheDocument();
    });

    test('should render total settled amount popover when settled amount is zero', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Total Settled Amount')).toBeInTheDocument();
      });
      expect(
        screen.getByText(
          'We are fetching Total Settled Amount, and it seems that some of the settlements are taking longer than expected.',
        ),
      ).toBeInTheDocument();

      const popoverIcon = screen.getByTestId('total-settled-amount-popover');
      expect(popoverIcon).toBeInTheDocument();

      userEvent.hover(popoverIcon);
      await waitFor(() => {
        expect(trackGaSpy).toHaveBeenCalled();
      });
      expect(trackGaSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Hover - Total Settled Amount Info Icon',
        eventLabel: 'Drawer | Total Settled Amount info icon',
      });
      expect(trackHoverEventsSpy).toHaveBeenCalled();
    });

    test('should render instant settlement modal section', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Requested Settlement Details')).toBeInTheDocument();
      });
      expect(screen.getByText('Requested Amount')).toBeInTheDocument();
      expect(screen.getByText('Requested at')).toBeInTheDocument();
    });

    test('should render payout details and call track events when view more details link is clicked', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Payout Details')).toBeInTheDocument();
      });
      expect(screen.getByText('Instant Details List')).toBeInTheDocument();
      expect(screen.getByText('View More Details')).toBeInTheDocument();

      const viewMoreLink = screen.getByRole('link');
      expect(viewMoreLink).toHaveAttribute('href', `/instantsettlement_details/${defaultProps.id}`);

      userEvent.click(viewMoreLink);
      await waitFor(() => {
        expect(closeModalSpy).toHaveBeenCalled();
      });

      expect(trackViewMoreClickEventsSpy).toHaveBeenCalled();
      expect(trackGaSpy).toHaveBeenCalled();
      expect(trackGaSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Click CTA - View More Details',
        eventLabel: 'Drawer | Click on View More Details CTA',
      });
    });
  });
});
