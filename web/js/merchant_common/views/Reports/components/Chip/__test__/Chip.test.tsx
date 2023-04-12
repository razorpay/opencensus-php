import React from 'react';
import { render, screen } from 'test-utils';
import { Chip } from 'merchant_common/views/Reports/components/Chip';

describe('Chip', () => {
  const child = 'Pending';

  const App = ({ children, ...otherProps }) => {
    return <Chip {...otherProps}>{children}</Chip>;
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
