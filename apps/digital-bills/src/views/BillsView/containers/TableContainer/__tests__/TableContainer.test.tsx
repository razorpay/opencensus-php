import React from 'react';
import { useQuery } from '@tanstack/react-query';

import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import TableContainer from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/TableContainer';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

describe('TableContainer', () => {
  test('should render the TableContainer component', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: false,
      isError: false,
      data: {},
    });

    const { getByText, getByRole, getAllByRole } = renderWithWrappers(<TableContainer />);
    const tabs = getAllByRole('tab');
    expect(tabs[0]).toHaveTextContent('Bills');
    expect(tabs[1]).toHaveTextContent('Store Data');

    // Bills table
    expect(getByRole('button', { name: 'Edit Columns' })).toBeInTheDocument();

    // Store Data table
    await userEvent.click(tabs[1]);
    expect(getByText('Store Details')).toBeInTheDocument();
  });
});
