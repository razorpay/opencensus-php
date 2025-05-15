import React from 'react';
import { render, screen } from 'test-utils';

import { PerformanceCard } from '../PerformaceCard';
import { PerformanceComponent } from '../types';

describe('Testing PerformanceCard Component', () => {
  const mockItem: PerformanceComponent['data']['cards'][number] = {
    id: 'some_random_id',
    label: 'Total Revenue',
    value: '10L',
  };

  it.each([true, false])('renders correctly when isMobile is %s', (isMobile) => {
    render(<PerformanceCard item={mockItem} variant="positive" index={1} isMobile={isMobile} />);
    expect(screen.getByText('#1')).toBeInTheDocument();
    expect(screen.getByText('Total Revenue')).toBeInTheDocument();
    expect(screen.getByText('10L')).toBeInTheDocument();
  });
});
