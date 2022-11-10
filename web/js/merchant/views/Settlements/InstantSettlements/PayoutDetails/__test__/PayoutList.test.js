import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PayoutList from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/PayoutList';
import { render, screen } from 'test-utils';
import {
  tableHeader,
  defaultProps,
  state,
} from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/__test__/mocks/fixtures/PayoutList';

describe('PayoutList', () => {
  const renderApp = ({ initialState = state, props } = {}) =>
    render(<PayoutList {...defaultProps} {...props} />, {
      initialState,
    });

  test('should render instant settlement payout list table', () => {
    renderApp();
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getAllByRole('table')).toHaveLength(1);
  });

  describe('PayoutListTable', () => {
    test('should render table head for payout listing', () => {
      renderApp();
      const tableHead = screen.getAllByRole('columnheader');
      expect(tableHead).toHaveLength(Object.keys(defaultProps.items[0]).length - 1);

      tableHeader.forEach((each) => {
        expect(screen.getByText(each)).toBeInTheDocument();
      });
    });

    test('should render payout list items', () => {
      renderApp();
      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(defaultProps.items.length + 1);

      defaultProps.items.forEach((each) => {
        expect(screen.getByText(each.id)).toBeInTheDocument();
        expect(screen.getAllByText(each.id)).toHaveLength(1);
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
      expect(tableHead).toHaveLength(Object.keys(defaultProps.items[0]).length);

      expect(screen.getByText('Deductions')).toBeInTheDocument();
    });

    test('should render empty table message when no payout detail item found', () => {
      renderApp({ props: { items: [] } });
      const emptyMessage = 'No Settlements found!';
      expect(screen.getByText(emptyMessage)).toBeInTheDocument();
      expect(screen.getByRole('cell', { name: emptyMessage })).toBeInTheDocument();
      expect(screen.getAllByRole('cell', { name: emptyMessage })).toHaveLength(1);

      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(2);
    });
  });
});
