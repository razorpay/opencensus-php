import React from 'react';
import { render, screen } from 'test-utils';
import { EmptyTable } from 'merchant_common/views/Reports/components';

describe('Empty Table', () => {
  const App = (props) => {
    return (
      <EmptyTable title="Test Table Is Empty" desc="empty table desc" src={props?.src}>
        <div aria-label="Empty Table Child" />
      </EmptyTable>
    );
  };

  test('should render children correctly', () => {
    render(<App />);
    expect(screen.getByText('Test Table Is Empty')).toBeInTheDocument();
    expect(screen.getByText('empty table desc')).toBeInTheDocument();
    expect(screen.queryByAltText('Empty Table Illustration')).toBeInTheDocument();
    expect(screen.getByLabelText('Empty Table Child')).toBeInTheDocument();
  });
});
