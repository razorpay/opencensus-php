import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as fixtures from 'merchant/views/Settlements/RouteOndemandSettlements/__test__/mocks/fixtures';
import SettlementsListItem from 'merchant/views/Settlements/RouteOndemandSettlements/components/RouteSettlementsList';
import { render, screen } from 'test-utils';

describe('RouteSettlementList', () => {
  const defaultProps = {
    settlements: [
      {
        id: 'AFGT674HBC',
        amount: 5000,
        total_amount_settled: 3400,
        total_amount_pending: 1600,
        created_at: '15-Nov-2018',
        status: 'pending',
      },
      {
        id: 'AFGT674WBC',
        amount: 6000,
        total_amount_settled: 4400,
        total_amount_pending: 1600,
        created_at: '15-Nov-2018',
        status: 'pending',
      },
    ],
    isLoading: false,
  };

  const App = (props) => <SettlementsListItem {...defaultProps} {...props} />;

  test('should render route settlement table', () => {
    render(<App />);
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getAllByRole('table')).toHaveLength(1);
  });

  describe('Settlement List', () => {
    test('should render table heading for listing', () => {
      render(<App />);
      const columnheader = screen.getAllByRole('columnheader');
      expect(columnheader).toHaveLength(Object.keys(defaultProps.settlements[0]).length);

      fixtures.tableHeader.forEach((each) => {
        expect(screen.getByText(each)).toBeInTheDocument();
      });
    });

    test('should render spinner when API is loading', () => {
      render(<App isLoading={true} />);
      expect(screen.getByText('Loading List')).toBeInTheDocument();
    });

    test('should render settlement list items', () => {
      render(<App />);
      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(defaultProps.settlements.length + 1);

      defaultProps.settlements.forEach((each) => {
        expect(screen.getByText(each.id)).toBeInTheDocument();
        expect(screen.getAllByText(each.id)).toHaveLength(1);
      });
    });

    test('should render empty table message when no settlement found', () => {
      const items = [];
      render(<App settlements={items} />);
      const emptyMessage = 'No Settlements found!';
      expect(screen.getByText(emptyMessage)).toBeInTheDocument();
      expect(screen.getByRole('cell', { name: emptyMessage })).toBeInTheDocument();
      expect(screen.getAllByRole('cell', { name: emptyMessage })).toHaveLength(1);

      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(2);
    });
  });
});
