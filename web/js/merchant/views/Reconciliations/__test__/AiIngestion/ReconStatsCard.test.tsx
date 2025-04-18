import React from 'react';
import { render, screen } from 'test-utils';

import ReconStatsCard from 'merchant/views/Reconciliations/AiIngestion/ReconStatsCard';

const defaultProps = {
  title: 'Total Revenue',
  value: '$10,000',
  subtitle: 'This Month',
  isLoading: false,
};

const renderReconStatsCard = (props = defaultProps) => {
  return render(<ReconStatsCard {...props} />);
};

describe('ReconStatsCard Component', () => {
  test('renders without errors', () => {
    expect(() => renderReconStatsCard()).not.toThrow();
  });

  test('displays title, value, and subtitle when not loading', () => {
    renderReconStatsCard();

    expect(screen.getByText(/total revenue/i)).toBeInTheDocument();
    expect(screen.getByText(/\$10,000/i)).toBeInTheDocument();
    expect(screen.getByText(/this month/i)).toBeInTheDocument();

    expect(screen.queryByLabelText(/load data/i)).not.toBeInTheDocument();
  });
});
