import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import BreakupTable from 'merchant/views/Settlements/Settlements/components/BreakupTable';
import { breakupTableProps } from 'merchant/views/Settlements/Settlements/components/__test__/mocks/fixtures/BreakupTable';

describe('BreakupTable.js', () => {
  const renderApp = (props = {}) => render(<BreakupTable {...props} />);

  test('should render table tag', () => {
    renderApp(breakupTableProps);
    expect(screen.queryByRole('table')).toBeInTheDocument();
  });

  test('should render columns correctly', () => {
    renderApp(breakupTableProps);
    expect(screen.queryByText('Component')).toBeInTheDocument();
    expect(screen.queryByText('Fee')).toBeInTheDocument();
    expect(screen.queryByText('Tax')).toBeInTheDocument();
    expect(screen.queryByText('Amount')).toBeInTheDocument();
    expect(screen.queryByText('Settled Amount')).toBeInTheDocument();
  });

  test('should render table data correctly', () => {
    renderApp(breakupTableProps);
    expect(screen.queryByText('Adjustment')).toBeInTheDocument();
    expect(screen.queryByText('Reversal')).toBeInTheDocument();
    expect(screen.queryByText('Refund Domestic')).toBeInTheDocument();
  });
});
