import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import BillDetails from '@apps/digital-bills/src/views/BillDetails';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: jest.fn(),
}));

describe('BillDetails', () => {
  (useParams as jest.Mock).mockReturnValue({ id: '123' });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render the BillDetails page', () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: false,
      isError: false,
      data: { billById: {} },
    });
    const { getByText } = renderWithWrappers(<BillDetails />);
    expect(getByText('Bill Details')).toBeInTheDocument();
  });

  test("should render the ErrorPage if 'BillById' API returns error", () => {
    (useQuery as jest.Mock).mockReturnValue({
      isLoading: false,
      isError: true,
      data: { billById: {} },
      refetch: jest.fn(),
    });
    const { getByText } = renderWithWrappers(<BillDetails />);
    expect(getByText('Error in fetching Bill info')).toBeInTheDocument();
  });
});
