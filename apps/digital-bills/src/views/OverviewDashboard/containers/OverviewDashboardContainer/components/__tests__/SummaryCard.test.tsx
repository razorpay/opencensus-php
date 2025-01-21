import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent, waitFor } from '@apps/digital-bills/src/services/test/test-utils';
import SummaryCard from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/SummaryCard';

describe('SummaryCard', () => {
  test('should render SummaryCard component', async () => {
    const { getByText, getAllByRole, getByTestId } = renderWithWrappers(
      <SummaryCard
        title="Test Title"
        amount={10000}
        heroImg="/testImage"
        subtitle="Test Sub-title"
        info="Test Tooltip"
      />,
    );
    expect(getByText('Test Title')).toBeInTheDocument();
    expect(getByText('10K')).toBeInTheDocument();
    expect(getAllByRole('img')).toHaveLength(2);
    expect(getByText('Test Sub-title')).toBeInTheDocument();
    const tooltipIcon = getByTestId('tooltip-interactive-wrapper');
    expect(tooltipIcon).toBeInTheDocument();
    await userEvent.hover(tooltipIcon);
    await waitFor(() => {
      expect(getByText('Test Tooltip')).toBeInTheDocument();
    });
  });

  test('should render SummaryCard component without subtitle and info, when not passed in props ', () => {
    const { getByText, queryByText, queryByTestId } = renderWithWrappers(
      <SummaryCard title="Test Title" amount={10000} heroImg="/testImage" />,
    );
    expect(getByText('Test Title')).toBeInTheDocument();
    expect(queryByText('Test Sub-title')).not.toBeInTheDocument();
    const tooltipIcon = queryByTestId('tooltip-interactive-wrapper');
    expect(tooltipIcon).not.toBeInTheDocument();
  });
});
