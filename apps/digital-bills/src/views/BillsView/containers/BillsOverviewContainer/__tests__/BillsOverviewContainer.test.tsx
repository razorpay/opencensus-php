import React from 'react';
import { useQuery } from '@tanstack/react-query';

import BillsOverviewContainer from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer';
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

describe('BillsOverviewContainer', () => {
  test('should render the BillsOverviewContainer component', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: false,
      isError: false,
      data: {},
    });

    const { getByRole } = renderWithWrappers(<BillsOverviewContainer />);
    expect(getByRole('link', { name: 'View Graphical Data' })).toBeInTheDocument();
  });
});
