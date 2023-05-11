import React from 'react';
import { render, screen } from 'test-utils';
import { StatusIndicator } from 'merchant_common/views/Reports/components/StatusIndicator';

describe('StatusIndicator', () => {
  const child = 'Pending';

  const App = ({ children, ...otherProps }) => {
    return <StatusIndicator {...otherProps}>{children}</StatusIndicator>;
  };

  test('should render children correctly', () => {
    render(<App>{child}</App>);
    expect(screen.getByText(child)).toBeInTheDocument();
  });

  test('should render indicator', () => {
    render(<App indicator>{child}</App>);
    expect(screen.getByLabelText('Indicator')).toBeInTheDocument();
  });
});
