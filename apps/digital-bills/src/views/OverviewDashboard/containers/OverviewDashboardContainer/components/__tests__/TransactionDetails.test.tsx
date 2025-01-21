import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import TransactionDetails from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/TransactionDetails';

jest.mock('react-chartjs-2', () => ({
  Doughnut: jest.fn(() => <span>Doughnut Chart</span>),
}));

describe('TransactionDetails', () => {
  test('should render TransactionDetails component', () => {
    const { getByText } = renderWithWrappers(
      <TransactionDetails totalTransactions={150} data={[]} />,
    );

    // TransactionDetails
    expect(getByText('Total Transactions')).toBeInTheDocument();
    expect(getByText('150')).toBeInTheDocument();

    // TransactionDoughnutChart
    expect(getByText('Doughnut Chart')).toBeInTheDocument();
  });
});
