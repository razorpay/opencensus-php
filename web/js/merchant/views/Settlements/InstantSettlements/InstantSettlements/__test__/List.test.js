import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import InstantSettlementsList from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/List';
import { render, screen, userEvent, waitFor } from 'test-utils';
import * as trackGa from 'common/utils/googleAnalytics';
import { state, defaultProps, tableHeader } from './mocks/fixtures/List';

describe('InstantSettlementsList', () => {
  const trackSpy = jest.spyOn(trackGa, 'setTrackData');

  const renderApp = ({ initialState = state, props } = {}) =>
    render(<InstantSettlementsList {...defaultProps} {...props} />, {
      initialState,
    });

  beforeEach(() => {
    trackSpy.mockClear();
  });

  test('should render instant settlement payout list table', () => {
    renderApp();
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getAllByRole('table')).toHaveLength(1);
  });

  describe('InstantSettlementsListTable', () => {
    test('should render table heading for instant settlements listing', () => {
      renderApp();
      const tableHead = screen.getAllByRole('columnheader');
      expect(tableHead).toHaveLength(tableHeader.length);

      tableHeader.forEach((each) => {
        expect(screen.getByText(each)).toBeInTheDocument();
      });
    });

    test('should render instant settlements list items', () => {
      renderApp();
      expect(screen.getAllByRole('row')).toHaveLength(defaultProps.settlements.length + 1);

      const settlementLinks = screen.getAllByRole('link');
      expect(settlementLinks).toHaveLength(defaultProps.settlements.length);

      defaultProps.settlements.forEach((each, index) => {
        expect(screen.getByText(each.id)).toBeInTheDocument();
        expect(screen.getAllByText(each.id)).toHaveLength(1);
        expect(settlementLinks[index]).toHaveAttribute('href', `/instantsettlement/${each.id}`);
      });
    });

    test('should render deduction column when user showOnDemandDeduction enabled', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            showOnDemandDeduction: true,
          },
        },
      };
      renderApp({ initialState });
      const tableHead = screen.getAllByRole('columnheader');
      expect(tableHead).toHaveLength(tableHeader.length + 1);

      expect(screen.getByText('Deductions')).toBeInTheDocument();
    });

    test('should call track event when clicked on instant settlement link', async () => {
      renderApp();
      const settlementLinks = screen.getAllByRole('link');
      userEvent.click(settlementLinks[1]);

      await waitFor(() => {
        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Click Id - Instant Settlement Id',
          eventLabel: 'Data Table | Click Instant Settlement ID',
        });
      });
    });

    test('should render empty table message when no settlement details found', () => {
      renderApp({ props: { settlements: [] } });
      const emptyMessage = 'No Settlements found!';
      expect(screen.getByText(emptyMessage)).toBeInTheDocument();
      expect(screen.getByRole('cell', { name: emptyMessage })).toBeInTheDocument();
      expect(screen.getAllByRole('cell', { name: emptyMessage })).toHaveLength(1);

      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(2);
    });
  });
});
