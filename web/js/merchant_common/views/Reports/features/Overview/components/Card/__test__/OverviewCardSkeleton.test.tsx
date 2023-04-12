import React from 'react';
import { render, screen } from 'test-utils';
import { CardSkeleton } from 'merchant_common/views/Reports/features/Overview/components/Card/Skeleton';

describe('OverviewCardSkeleton', () => {
  test('should render card component without any error', () => {
    render(<CardSkeleton />);
    expect(screen.getByLabelText(`Card Icon`)).toBeInTheDocument();
    expect(screen.getByLabelText(`Card Title`)).toBeInTheDocument();
    expect(screen.getByLabelText(`Card Desc`)).toBeInTheDocument();
    expect(screen.getByLabelText(`Card Link`)).toBeInTheDocument();
  });
});
