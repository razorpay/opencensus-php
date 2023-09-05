import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import InstantSettlements from 'merchant/views/Settlements/InstantSettlements/InstantSettlements';
import { screen, render, waitFor, server, userEvent } from 'test-utils';
import * as trackGa from 'common/utils/googleAnalytics';
import * as trackEvents from 'merchant/views/Settlements/trackEvents';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as detailsAction from 'merchant/reducers/settlements/details';
import * as homeAction from 'merchant/reducers/home';
import * as handlers from './mocks/handlers';
import { state, defaultProps } from './mocks/fixtures';

describe('InstantSettlements', () => {
  const trackGaSpy = jest.spyOn(trackGa, 'setTrackData');
  const trackEventsSpy = jest.spyOn(trackEvents, 'trackOnDemandSearchClick');
  const modalActionSpy = jest.spyOn(ModalActions, 'openModal');
  const detailsActionSpy = jest.spyOn(detailsAction, 'fetchHolidayList');
  const homeActionSpy = jest.spyOn(homeAction, 'fetchOndemandRestrictions');

  const renderApp = ({ initialState = state, props } = {}) =>
    render(<InstantSettlements {...defaultProps} {...props} />, {
      initialState,
    });

  beforeEach(() => {
    trackGaSpy.mockClear();
    trackEventsSpy.mockClear();
    modalActionSpy.mockClear();
    detailsActionSpy.mockClear();
    homeActionSpy.mockClear();
  });

  test('should fetch holiday list on mount', async () => {
    renderApp();
    await waitFor(() => {
      expect(detailsActionSpy).toHaveBeenCalledTimes(1);
    });
  });

  test('should fetch on-demand settlement restriction when settlement is restricted', async () => {
    renderApp({
      initialState: {
        ...state,
        session: {
          ...state.session,
          user: { isFeatureEnabled: (feature) => feature === 'es_on_demand_restricted' },
        },
      },
    });
    await waitFor(() => {
      expect(homeActionSpy).toHaveBeenCalledTimes(1);
    });
  });

  describe('SettlementView', () => {
    test('should render empty settle now when user settlement is enabled and item count is zero', async () => {
      renderApp({
        initialState: {
          ...state,
          session: {
            ...state.session,
            user: {
              ...state.session.user,
              isOndemandSettlementEnabled: true,
            },
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('The wait is over...')).toBeInTheDocument();
      });
      const settleNowButton = screen.getByRole('button', { name: /Settle Now/i });
      userEvent.click(settleNowButton);

      await waitFor(() => {
        expect(modalActionSpy).toHaveBeenCalledTimes(1);
      });
      expect(modalActionSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          size: 'small',
          disableClose: true,
          queryParams: {
            action: 'instant-settlement',
          },
        }),
      );
    });

    test('should redirect to settlements when user settlement is disabled', async () => {
      const { history } = renderApp({
        initialState: {
          ...state,
          session: {
            ...state.session,
            user: {
              ...state.session.user,
              isOndemandSettlementEnabled: false,
            },
          },
        },
      });
      await waitFor(() => {
        expect(history.location.pathname).toEqual('/settlements');
      });
    });

    // TODO: fix this test cases
    test.skip('should render settlement filter and view when instant settlement items are available', async () => {
      server.use(handlers.instantsettlementsHandlers());
      renderApp({
        initialState: {
          ...state,
          session: {
            ...state.session,
            user: {
              ...state.session.user,
              isOndemandSettlementEnabled: true,
            },
          },
        },
      });

      await waitFor(() => {
        expect(screen.getByRole('form')).toBeInTheDocument();
      });
      expect(screen.getByRole('form')).toHaveAttribute('name', 'instantSettlementListFilter');

      userEvent.click(
        screen.getByRole('button', {
          name: 'Search',
        }),
      );
      await waitFor(() => {
        expect(trackGaSpy).toHaveBeenCalledTimes(1);
      });
      expect(trackGaSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Click CTA - Search Instant Settlements',
        eventLabel: 'Data Table | Search CTA',
      });
      expect(trackEventsSpy).toHaveBeenCalledTimes(1);

      await userEvent.click(
        screen.getByRole('button', {
          name: 'Clear',
        }),
      );
      expect(trackGaSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Click CTA - Clear Instant Settlements',
        eventLabel: 'Data Table | Clear CTA',
      });
    });
  });
});
