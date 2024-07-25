import React from 'react';
import StatusBadge from '../StatusBadge';
import { render, screen } from 'apps/pos/src/services/test/test-utils';

describe('<StatusBadge />', () => {
  test('should render Status Badge with value', () => {
    render(<StatusBadge type="pending" />);
    expect(screen.getByText('Pending')).toBeInTheDocument();
  });
  test('should not render Status Badge with unknown status value', () => {
    render(<StatusBadge type="some_random" />);
    expect(screen.getByTestId('component-wrapper').firstChild).toBeNull();
  });
});
