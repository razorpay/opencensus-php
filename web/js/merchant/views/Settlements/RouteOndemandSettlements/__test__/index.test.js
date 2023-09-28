import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import './mocks/fixtures';
import RouteOndemandSettlements from 'merchant/views/Settlements/RouteOndemandSettlements';
import { render, screen, waitFor, server } from 'test-utils';
import * as handlers from './mocks/handlers';

const state = {
  session: {
    user: {},
  },
  routeOndemandSettlements: {
    loading: false,
    items: [],
    error: null,
  },
};

describe('RouteOndemandSettlements', () => {
  test('should redirect when on-demand settlement is not enabled', async () => {
    const { history } = render(<RouteOndemandSettlements />);
    await waitFor(() => {
      expect(history.location.pathname).toEqual('/settlements');
    });
  });

  describe('onDemand Settlement enabled', () => {
    const initialState = {
      ...state,
      session: {
        user: {
          isOndemandSettlementEnabled: true,
        },
      },
      routeOndemandSettlements: {
        loading: false,
        items: [
          {
            id: 'AFGT674HBC',
            amount: 5000,
            total_amount_settled: 3400,
            total_amount_pending: 1600,
            created_at: '15-NOV-2018',
            status: 'pending',
          },
        ],
      },
    };

    test('should render settlement list filter when on-demand settlement is enabled', () => {
      render(<RouteOndemandSettlements />, { initialState });
      const form = screen.getByRole('form');
      expect(form).toBeInTheDocument();
      expect(form).toHaveAttribute('name', 'instantRouteSettlementListFilter');

      const textbox = screen.getByRole('textbox');
      expect(textbox).toBeInTheDocument();
      expect(textbox).toHaveAttribute('name', 'id');

      const combobox = screen.getByRole('combobox');
      expect(combobox).toBeInTheDocument();
      expect(combobox).toHaveAttribute('name', 'status');
    });

    test('should render route settlement list when on-demand Settlement is enabled', () => {
      server.use(handlers.settlementSuccesshandler({ initialState }));

      render(<RouteOndemandSettlements />, { initialState });
      expect(screen.getByRole('table')).toBeInTheDocument();
      expect(screen.getAllByRole('table')).toHaveLength(1);

      expect(screen.getByText('Requested Amount')).toBeInTheDocument();
      expect(screen.getByText('Settled Amount')).toBeInTheDocument();
      expect(screen.getByText('Pending Amount')).toBeInTheDocument();
      expect(screen.getByText('Created At')).toBeInTheDocument();
    });

    test('should render pager view when on-demand settlement is enabled', () => {
      render(<RouteOndemandSettlements />, { initialState });
      expect(screen.getByText('Pager Details')).toBeInTheDocument();
      expect(screen.getByText(`Showing 1 - 0`)).toBeInTheDocument();
    });

    test('should render error alert on-demand settlement when API fails with error', async () => {
      server.use(handlers.settlementErrorhandler());

      const settlementState = {
        ...initialState,
        routeOndemandSettlements: {
          loading: false,
          error: 'Error in fetching ondemand settlements from server',
        },
      };

      render(<RouteOndemandSettlements />, { initialState });
      await waitFor(() => {
        expect(
          screen.getByText(settlementState.routeOndemandSettlements.error),
        ).toBeInTheDocument();
      });
    });
  });
});
