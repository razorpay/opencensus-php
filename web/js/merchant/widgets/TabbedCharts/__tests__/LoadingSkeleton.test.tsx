import React from 'react';

import LoadingSkeleton from 'merchant/widgets/TabbedCharts/LoadingSkeleton';
import { render, screen } from 'test-utils';

describe('LoadingSkeleton', () => {
  const renderApp = () => {
    render(<LoadingSkeleton count={undefined} />);
  };
  it('renders without crashing', () => {
    renderApp();
    expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
  });

  it('renders three TabSkeleton components', () => {
    renderApp();
    const tabSkeletons = screen.getAllByTestId('tab-skeleton');
    expect(tabSkeletons).toHaveLength(3);
  });

  it('renders the first TabSkeleton as active', () => {
    renderApp();
    const activeTabSkeleton = screen.getAllByTestId('tab-skeleton')[0];
    expect(activeTabSkeleton).toHaveStyle({ borderBottomColor: 'brand.primary.500' });
  });

  it('renders the other TabSkeletons as inactive', () => {
    renderApp();
    const inactiveTabSkeletons = screen.getAllByTestId('tab-skeleton').slice(1);
    inactiveTabSkeletons.forEach((skeleton) => {
      expect(skeleton).toHaveStyle({ borderBottomColor: 'brand.primary.300' });
    });
  });
});
