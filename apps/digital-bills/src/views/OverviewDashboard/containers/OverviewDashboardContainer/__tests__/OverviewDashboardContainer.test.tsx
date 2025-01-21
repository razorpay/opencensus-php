import React from 'react';
import { useQuery } from '@tanstack/react-query';

import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import OverviewDashboardContainer from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';

jest.mock('react-chartjs-2', () => ({
  Doughnut: jest.fn(() => <span>Doughnut Chart</span>),
}));

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

describe('OverviewDashboardContainer', () => {
  test('should render the OverviewDashboardContainer component', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: false,
      isError: false,
      data: { merchantOnboardingStatus: 'ACTIVATED' },
    });

    const { getByText, getByRole } = renderWithWrappers(<OverviewDashboardContainer />);
    expect(getByText('Bills View')).toBeInTheDocument();
    expect(getByText('Campaigns')).toBeInTheDocument();
    expect(getByText('Miscellaneous')).toBeInTheDocument();

    const goToBillsViewBtn = getByRole('button', { name: 'Go to Bills View' });
    expect(goToBillsViewBtn).toBeInTheDocument();
    await userEvent.click(goToBillsViewBtn);
    expect(window.location.pathname).toBe('/bills/');
  });
});
