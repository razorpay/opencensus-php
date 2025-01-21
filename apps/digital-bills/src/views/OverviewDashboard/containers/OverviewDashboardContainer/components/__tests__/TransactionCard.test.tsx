import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import TransactionCard from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/TransactionCard';

describe('TransactionCard', () => {
  test('should render TransactionCard component', () => {
    const { getByText } = renderWithWrappers(<TransactionCard title="Test Title" amount={10000} />);
    expect(getByText('Test Title')).toBeInTheDocument();
    expect(getByText('10K')).toBeInTheDocument();
  });
});
