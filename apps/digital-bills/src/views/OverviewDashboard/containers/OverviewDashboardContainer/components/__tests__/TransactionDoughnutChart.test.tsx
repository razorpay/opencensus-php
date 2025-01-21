import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import TransactionDoughnutChart from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/TransactionDoughnutChart';

jest.mock('react-chartjs-2', () => ({
  Doughnut: jest.fn(() => <span>Doughnut Chart</span>),
}));

const TRANSACTION_DOUGHNUT_CHART_DATA = [
  {
    colour: 'green',
    legend: 'Digital',
    value: 100,
  },
  {
    colour: 'blue',
    legend: 'Print',
    value: 200,
  },
];

describe('TransactionDoughnutChart', () => {
  test('should render TransactionDoughnutChart component', () => {
    const { getByText } = renderWithWrappers(
      <TransactionDoughnutChart data={TRANSACTION_DOUGHNUT_CHART_DATA} />,
    );

    // Chart
    expect(getByText('Doughnut Chart'));

    // Legend 1
    const firstLegendName = getByText('Digital');
    const firstLegendIdentifier = firstLegendName.previousElementSibling;
    expect(firstLegendName).toBeInTheDocument();
    expect(getByText('100')).toBeInTheDocument();
    expect(firstLegendIdentifier).toHaveStyle('backgroundColor: green');
    expect(firstLegendIdentifier).toHaveStyle('borderColor: green');

    // Legend 2
    const secondLegendName = getByText('Print');
    const secondLegendIdentifier = secondLegendName.previousElementSibling;
    expect(secondLegendName).toBeInTheDocument();
    expect(getByText('200')).toBeInTheDocument();
    expect(secondLegendIdentifier).toHaveStyle('backgroundColor: blue');
    expect(secondLegendIdentifier).toHaveStyle('borderColor: blue');
  });
});
